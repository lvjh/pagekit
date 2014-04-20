<?php

namespace Pagekit\Editor;

use Pagekit\Editor\Event\EditorLoadEvent;
use Pagekit\Framework\Event\EventSubscriber;
use Pagekit\System\Event\TmplEvent;

class HtmlEditor extends EventSubscriber implements EditorInterface
{
    /**
     * @var array
     */
    protected $plugins = array();

    /**
     * @return array
     */
    public function getPlugins()
    {
        return $this->plugins;
    }

    /**
     * @param  string $name
     * @return mixed
     */
    public function getPlugin($name)
    {
        return isset($this->plugins[$name]) ? $this->plugins[$name] : null;
    }

    /**
     * @param string $name
     * @param mixed  $callback
     */
    public function addPlugin($name, $callback)
    {
        $this->plugins[$name] = $callback;
    }

    /**
     * @param string $name
     */
    public function removePlugin($name)
    {
        unset($this->plugins[$name]);
    }

    /**
     * {@inheritdoc}
     *
     * TODO: refactor finder options
     */
    public function render($value, array $attributes = array())
    {
        $this('view.scripts')->queue(
            'editor.markdown', 'extension://system/assets/js/editor/markdown.js', 'requirejs',
            array(
                'data-plugins' => json_encode(array_values($this->getPlugins())),
                'data-finder'  => json_encode(array('root' => $this('config')->get('app.storage'), 'mode' => 'write', 'hash' => $this('finder')->getToken($this('config')->get('app.storage'), 'write')))
            )
        );

        $attributes = array_merge(array('data-editor' => 'markdown', 'autocomplete' => 'off', 'style' => 'visibility:hidden; height:543px;'), $attributes);

        return sprintf('<textarea%s>%s</textarea>', $this->getAttributes($attributes), htmlspecialchars($value));
    }

    /**
     * Get html attribute string
     *
     * @param  array $attributes
     * @return string
     */
    protected function getAttributes($attributes)
    {
        $html = '';

        foreach ($attributes as $name => $val) {
            $html .= sprintf(' %s="%s"', $name, htmlspecialchars($val));
        }

        return $html;
    }

    /**
     * Loads the editor.
     */
    public function onEditorLoad(EditorLoadEvent $event)
    {
        if ('markdown' != $event['editor']) {
            return;
        }

        $event->setEditor($this);

        $this->addPlugin('link', 'extensions/system/assets/js/editor/link');
        $this->addPlugin('video', 'extensions/system/assets/js/editor/video');
        $this->addPlugin('image', 'extensions/system/assets/js/editor/image');
    }

    /**
     * Register Tmpl's callback.
     *
     * @param TmplEvent $event
     */
    public function onSystemTmpl(TmplEvent $event)
    {
        $event->register('image.modal', 'extension://system/assets/tmpl/image.modal.razr.php');
        $event->register('image.replace', 'extension://system/assets/tmpl/image.replace.razr.php');
        $event->register('link.modal', 'extension://system/assets/tmpl/link.modal.razr.php');
        $event->register('link.replace', 'extension://system/assets/tmpl/link.replace.razr.php');
        $event->register('video.modal', 'extension://system/assets/tmpl/video.modal.razr.php');
        $event->register('video.replace', 'extension://system/assets/tmpl/video.replace.razr.php');
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return array(
            'editor.load' => array('onEditorLoad', 8),
            'system.tmpl' => 'onSystemTmpl'
        );
    }
}