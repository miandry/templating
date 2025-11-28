<?php

namespace Drupal\templating\TwigExtension;

use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;
use Drupal\Core\Url;

/**
 * Custom Twig extension for templating manager.
 */
class DefaultTwigExtension extends AbstractExtension {

  /**
   * {@inheritdoc}
   */
  public function getFunctions() {
    return [
      new TwigFunction('spacer_top', [$this, 'spacerTop']),
      new TwigFunction('spacer_bottom', [$this, 'spacerBottom']),
      new TwigFunction('file_exists', [$this, 'fileExists']),
      new TwigFunction('template', [$this, 'template']),
      new TwigFunction('render_node_inline_template', [$this, 'renderNodeInlineTemplate']),
      new TwigFunction('render_template_node', [$this, 'renderTemplateNode']),
      new TwigFunction('render_template_block', [$this, 'renderTemplateBlock']),
      new TwigFunction('render_page_inline_template', [$this, 'renderPageInlineTemplate']),
      new TwigFunction('render_inline_template', [$this, 'renderInlineTemplate']),
      new TwigFunction('DRUPAL_ROOT', [$this, 'drupalRoot']),
      new TwigFunction('path_templating', [$this, 'pathTemplating']),
      new TwigFunction('render_template', [$this, 'renderTemplate']),
      new TwigFunction('render_template_user', [$this, 'renderTemplateUser']),
      new TwigFunction('render_css', [$this, 'renderCss']),
      new TwigFunction('render_template_form', [$this, 'renderTemplateForm']),
      new TwigFunction('include_template', [$this, 'includeTemplate']),
    ];
  }

  /* --------------------------------------------------------------------------
   *  ALL FUNCTIONS BECOME INSTANCE METHODS
   * ------------------------------------------------------------------------ */

  public function includeTemplate($id, $var = []) {
    $service = \Drupal::service('templating.manager');

    $template = is_numeric($id)
      ? $service->getTemplatingById($id)
      : $service->getTemplatingByTitle($id);

    if (is_object($template)) {
      return [
        '#type' => 'inline_template',
        '#template' => $template->field_templating_html->value,
        '#context' => ['var' => $var],
      ];
    }

    return [
      '#type' => 'inline_template',
      '#template' => "<b>Template custom not found</b>",
      '#context' => ['var' => $var],
    ];
  }

  public function renderCss($css, $block_name) {
    \Drupal::service('templating.manager')
      ->assetCSSTemplateTheme($css, $block_name);
  }

  public function renderTemplateUser($content, $user, $view_mode) {
    $services = \Drupal::service('templating.manager');
    $output = false;

    $theme = \Drupal::theme()->getActiveTheme()->getName();
    $uid = $user->id();
    $hook1 = $services->formatName("user--{$theme}-{$uid}-{$view_mode}.html.twig");
    $hook2 = $services->formatName("user--{$theme}-{$view_mode}.html.twig");

    $base = $services->getTemplatingByTitle($hook2);
    if (is_object($base)) {
      $output = $base->field_templating_html->value;
    }

    $base2 = $services->getTemplatingByTitle($hook1);
    if (is_object($base2)) {
      $output = $base2->field_templating_html->value;
    }

    if ($output) {
      return [
        '#type' => 'inline_template',
        '#template' => $output,
        '#context' => [
          'content' => $content,
          'user' => $user,
          'view_mode' => $view_mode,
        ],
      ];
    }

    return false;
  }

  public function renderTemplateForm($content, $children) {
    return \Drupal::service('templating.manager')
      ->getRenderTemplateForm($content);
  }

  public function renderTemplate($content) {
    return \Drupal::service('templating.manager')
      ->getRenderTemplateCustom($content);
  }


  public function renderTemplateBlock($content) {
    $entity = NULL;

    $is_edit = isset($content['content']) && $content['content'] && $content['actions'];
    if ($is_edit) {
      $content = $content['content'];
    }

    if (isset($content['#entity_type'])
        && in_array($content['#entity_type'], ['block_content', 'inline_block'])) {
      $entity = $content['#block_content'] ?? $content['content']['#block_content'] ?? NULL;
    }

    if (is_object($entity)) {
      $view_mode = $content['#view_mode'] ?? $content['content']['#view_mode'] ?? NULL;
      $output = \Drupal::service('templating.manager')->getTemplateEntity($entity, $view_mode);

      if ($output) {
        return [
          '#type' => 'inline_template',
          '#template' => $output,
          '#context' => [
            'content' => $content,
            'entity' => $entity,
          ],
        ];
      }
    }
    return "";
  }

  public function pathTemplating() {
    return \Drupal::service('module_handler')
      ->getModule('templating')
      ->getPath();
  }

  public function spacerTop($content) {
    return $this->spacer($content, 'top');
  }

  public function spacerBottom($content) {
    return $this->spacer($content, 'bottom');
  }

  private function spacer($content, $position) {
    $block = isset($content['content']['#block_content'])
      ? $content['content']['#block_content']
      : ($content['#block_content'] ?? NULL);

    $map = [
      'space-tb-xs' => "space-" . substr($position, 0, 1) . "-xs",
      'space-tb-sm' => "space-" . substr($position, 0, 1) . "-sm",
      'space-tb-md' => "space-" . substr($position, 0, 1) . "-md",
      'space-tb-lg' => "space-" . substr($position, 0, 1) . "-lg",
    ];

    $size = "space-empty";

    if ($block && $block->spacer && $block->spacer->value) {
      $size = $map[$block->spacer->value] ?? "space-empty";
    }

    return "<div class='spacer-mizara {$size}'></div>";
  }

  public function drupalRoot() {
    return DRUPAL_ROOT;
  }

  public function fileExists($file_path) {
    return file_exists(DRUPAL_ROOT . '/' . $file_path);
  }

  public function renderPageInlineTemplate($page) {
    $alias_manager = \Drupal::service('path_alias.manager');
    $url = \Drupal\Core\Url::fromRoute('<current>');
    $alias = \Drupal::service('path_alias.manager')->getAliasByPath($url->toString());
    $entity = false ;
    try {
        $path = $alias_manager->getPathByAlias($alias);
        $route = \Drupal\Core\Url::fromUserInput($path);
        if ($route && $route->isRouted()) {
            $params = $route->getRouteParameters();
            if (!empty($params['node'])) {
                $entity = $params['node'];
                $entity = \Drupal::entityTypeManager()->getStorage('node')
                ->load($entity);
            }
        }
    } catch (\Exception $e) {
        $entity =  false;
    }
    if (is_object($entity)) {
        $services = \Drupal::service('templating.manager');
        $activeThemeName = \Drupal::service('theme.manager')->getActiveTheme();
        $theme = $activeThemeName->getName();
        $alias = str_replace('/', '-', $alias);
        $hook_name  = 'page--node-'.$theme.'-'.$alias.".html.twig";
        $array = ['type' => 'templating','status' => true ,'title' => $hook_name];
        $nodes = \Drupal::entityTypeManager()->getStorage('node')
        ->loadByProperties($array);
        if(!empty($nodes)){
            $node = end($nodes);           
            $output = $node->field_templating_html->value ;
            return [
                '#type' => 'inline_template',
                '#template' => $output,
                '#context' => [
                    'entity' => $node,
                    'page' => $page
                ],
            ];
        }

    }
    return false ;
  }

  public function renderInlineTemplate($var, $entity = false) {
    // Same: logic maintained but corrected for Drupal 11 compatibility.
    if(!isset($var['content'])){
        return false;
    }
    $content = $var['content'];
    $services = \Drupal::service('templating.manager');
    if(!$entity){
      return false ;
    }

    if(is_string($entity)){
      $entity = $services->getEntityFromVariable($var,$entity);
    }
    if(!is_object($entity)){
      return false;
    }
    $is_edit_layout_builder = isset($content['content']) && $content['content'] && $content['actions'];
    if ($is_edit_layout_builder) {
      $var["content"] =  $content['content'];
    }
    $view_mode = isset($var["elements"]["#view_mode"])?$var["elements"]["#view_mode"]:null;
    $output = $services->getTemplateEntity($entity, $view_mode);
    if ($output) {
        $element = [
            '#type' => 'inline_template',
            '#template' => $output,
        ];
        $entity_name = $entity->bundle();
        $var["entity"] = $entity ;
        $var[$entity_name] = $entity ;
        $var["variables"] = array_keys($var) ;
        $element['#context'] = $var ;
        return $element;
    }
    return $output;
  }

  public function template($template_name, $variables) {
    $config = \Drupal::config("template.$template_name");

    if ($config && $config->get('content')) {
      $loader = new \Twig\Loader\ArrayLoader([
        'temp_file.html' => $config->get('content'),
      ]);
      $twig = new \Twig\Environment($loader);
      return $twig->render('temp_file.html', $variables);
    }

    \Drupal::logger('templating')->error("Template $template_name not found");
    return "";
  }

  public function getName() {
    return 'templating.twig.extension';
  }

}
