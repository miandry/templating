<?php

namespace Drupal\templating\TwigExtension;


use Twig\TwigFunction;
use Twig\Extension\AbstractExtension;
use Drupal\Core\Url;
/**
 * Class DefaultTwigExtension.
 */
class DefaultTwigExtension extends AbstractExtension
{

    /**
     * {@inheritdoc}
     */
    public function getTokenParsers()
    {
        return [];
    }

    /**
     * {@inheritdoc}
     */
    public function getNodeVisitors()
    {
        return [];
    }

    /**
     * {@inheritdoc}
     */
    public function getFilters()
    {
        return [];
    }

    /**
     * {@inheritdoc}
     */
    public function getTests()
    {
        return [];
    }

    /**
     * {@inheritdoc}
     */
    public function getFunctions()
    {
        return [
            new TwigFunction('spacer_top', ['Drupal\templating\TwigExtension\DefaultTwigExtension', 'spacer_top_twig']),
            new TwigFunction('spacer_bottom', ['Drupal\templating\TwigExtension\DefaultTwigExtension', 'spacer_bottom_twig']),

            new TwigFunction('file_exists', ['Drupal\templating\TwigExtension\DefaultTwigExtension', 'file_exists_twig']),

            new TwigFunction('template', ['Drupal\templating\TwigExtension\DefaultTwigExtension', 'template_twig']),
            new TwigFunction('render_node_inline_template', ['Drupal\templating\TwigExtension\DefaultTwigExtension', 'render_node_inline_template_twig']),
            new TwigFunction('render_template_node', ['Drupal\templating\TwigExtension\DefaultTwigExtension', 'render_template_node_twig']),
            new TwigFunction('render_template_block', ['Drupal\templating\TwigExtension\DefaultTwigExtension', 'render_template_block_twig']),
            new TwigFunction('render_page_inline_template', ['Drupal\templating\TwigExtension\DefaultTwigExtension', 'render_page_inline_template_twig']),
   
            new TwigFunction('render_inline_template', ['Drupal\templating\TwigExtension\DefaultTwigExtension', 'render_inline_template_twig']),
            new TwigFunction('DRUPAL_ROOT', ['Drupal\templating\TwigExtension\DefaultTwigExtension', 'DRUPAL_ROOT_TWIG']),
            new TwigFunction('path_templating', ['Drupal\templating\TwigExtension\DefaultTwigExtension', 'path_templating']),
            new TwigFunction('render_template', ['Drupal\templating\TwigExtension\DefaultTwigExtension', 'render_template']),
            new TwigFunction('render_template_user', ['Drupal\templating\TwigExtension\DefaultTwigExtension', 'render_template_user']),
            new TwigFunction('render_css', ['Drupal\templating\TwigExtension\DefaultTwigExtension', 'render_css_twig']),
            new TwigFunction('render_template_form', ['Drupal\templating\TwigExtension\DefaultTwigExtension', 'render_template_form']),
            new TwigFunction('include_template', ['Drupal\templating\TwigExtension\DefaultTwigExtension', 'include_template_twig']),
   
        ];
    }
    public static function include_template_twig($id,$var = []){
        $service = \Drupal::service('templating.manager');
        if(is_numeric($id)){
            $template= $service->getTemplatingById($id);
        }else{
            $template= $service->getTemplatingByTitle($id);
        }
        if(is_object($template)){
            $output = $template->field_templating_html->value;
            return [
                '#type' => 'inline_template',
                '#template' => $output,
                'status' => true ,
                '#context' => [
                  'var' => $var,
                ],
              ];
        }else{
            $output = "<b>Template custom not find</b>";
            return [
                '#type' => 'inline_template',
                '#template' => $output,
                'status' => false,
                '#context' => [
                  'var' => $var,
                ],
              ];
        }
     
    }
    
    public static function render_css_twig($css,$block_name)
  {
    $services = \Drupal::service('templating.manager');
    $services->assetCSSTemplateTheme($css,$block_name);
  }  
  public static function render_template_user($content,$user,$view_mode)
  {
    $services = \Drupal::service('templating.manager');
    $output = false ;
    $current_theme = \Drupal::theme()->getActiveTheme();
    $theme = $current_theme->getName();
    $config_name_id = $services->formatName("user--" .$theme . "-" .$uid . "-" . trim($view_mode) . ".html.twig");
    $hook_name_base =  $services->formatName("user--".$theme."-".$view_mode.".html.twig");
    $content_base =  $services ->getTemplatingByTitle($hook_name_base);
    if (is_object($content_base)) {
      $output = $content_base->field_templating_html->value;
    }

    $content_basee_id =  $services ->getTemplatingByTitle($config_name_id);
    if (is_object($content_basee_id)) {
      $output = $content_basee_id->field_templating_html->value;
    }
    if ($output) {
      return [
        '#type' => 'inline_template',
        '#template' => $output,
        '#context' => [
          'content' => $content,
          'user' => $user,
          'view_mode' => $view_mode
        ],
      ];
    }
    return false;
  }
  public static function render_template_form($content,$children)
  {
    $services = \Drupal::service('templating.manager');
    return $services->getRenderTemplateForm($content);
  }

   public static function render_template($content)
   {
     $services = \Drupal::service('templating.manager');
     return $services->getRenderTemplateCustom($content);
   }
    public static function render_template_block_twig($content)
    {   $entity = false ;
        $is_edit_layout_builder = isset($content['content']) && $content['content'] && $content['actions'];
        if ($is_edit_layout_builder) {
           $content = $content['content'];
        }
        if (isset($content['#entity_type'])
        && ($content['#entity_type'] == "block_content"
            || $content['#entity_type'] == "inline_block")) {
             $entity = isset($content['#block_content']) ? $content['#block_content'] : $content['content']['#block_content'];
        }
        if (is_object($entity)) {
            $view_mode = isset($content['#view_mode']) ? $content['#view_mode'] : $content['content']['#view_mode'];
            $services = \Drupal::service('templating.manager');
            $output = $services->getTemplateEntity($entity, $view_mode);

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

    public static function path_templating()
    {
        $module_handler = \Drupal::service('module_handler');
        return $module_handler->getModule('templating')->getPath();
    }
    public static function spacer_top_twig($content)
    {
        if (isset($content['content']) && $content['content']['#block_content']) {
            $block = $content['content']['#block_content'];
        } else {
            $block = isset($content['#block_content']) ? ($content['#block_content']) : null;
        }
        $size = "space-empty";
        if ($block && $block->spacer && $block->spacer->value) {
            switch ($block->spacer->value) {
                case "space-tb-xs":
                    $size = "space-t-xs";
                    break;
                case "space-t-xs":
                    $size = "space-t-xs";
                    break;
                case "space-tb-sm":
                    $size = "space-t-sm";
                    break;
                case "space-t-sm":
                    $size = "space-t-sm";
                    break;
                case "space-tb-md":
                    $size = "space-t-md";
                    break;
                case "space-t-md":
                    $size = "space-t-md";
                    break;
                case "space-tb-lg":
                    $size = "space-t-lg";
                    break;
                case "space-t-lg":
                    $size = "space-t-lg";
                    break;
            }
        }
        return "<div class='spacer-mizara " . $size . "'></div>";
    }
    public static function spacer_bottom_twig($content)
    {
        if (isset($content['content']) && $content['content']['#block_content']) {
            $block = $content['content']['#block_content'];
        } else {
            $block = isset($content['#block_content']) ? ($content['#block_content']) : null;
        }
        $size = "space-empty";
        if ($block && $block->spacer && $block->spacer->value) {
            switch ($block->spacer->value) {
                case "space-tb-xs":
                    $size = "space-b-xs";
                    break;
                case "space-b-xs":
                    $size = "space-b-xs";
                    break;
                case "space-tb-sm":
                    $size = "space-b-sm";
                    break;
                case "space-b-sm":
                    $size = "space-b-sm";
                    break;
                case "space-tb-md":
                    $size = "space-b-md";
                    break;
                case "space-b-md":
                    $size = "space-b-md";
                    break;
                case "space-tb-lg":
                    $size = "space-b-lg";
                    break;
                case "space-b-lg":
                    $size = "space-b-lg";
                    break;
            }
        }
        return "<div class='spacer-mizara " . $size . "'></div>";
    }
    public static function DRUPAL_ROOT_TWIG()
    {
        return DRUPAL_ROOT;
    }
    public static function file_exists_twig($file_path)
    {
        return file_exists(DRUPAL_ROOT . '/' . $file_path);
    }
    public static function render_page_inline_template_twig($page)
    { 
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

    public static function render_inline_template_twig($var,$entity = false)
    {   
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
    public static function template_twig($template_name, $variables)
    {
        $suggestion_1 = "template." . $template_name;
        $config_current = \Drupal::config($suggestion_1);
        if (is_array($variables) && $config_current && $config_current->get('content')) {
            $loader = new \Twig\Loader\ArrayLoader([
                'Temp_file.html' => $config_current->get('content'),
            ]);
            $twig = new \Twig\Environment($loader);
            return $twig->render('Temp_file.html', $variables);
        } else {
            $message = 'Template   ' . $template_name . ' not exist';
            \Drupal::logger("templating")->error($message);
            return "";
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getOperators()
    {
        return [];
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'templating.twig.extension';
    }

}
