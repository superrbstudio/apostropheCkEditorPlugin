<?php

/**
 * @class aEditorCk
 * Implements CK editor support. @see aEditor for how to implement
 * support for other editors
 *
 */
 
class aEditorCk extends aEditor
{
  /**
   * 
   * @param  string $name        The element name
   * @param  string $value       The value displayed in this widget
   * @param  array  $options     The options set on the aWidgetFormRichTextarea object (id, tool, width, height)
   * @param  array  $attributes  An array of HTML attributes to be merged with the default HTML attributes
   * @param  array  $errors      An array of errors for the field
   * @return string An HTML tag string
   * @see aEditor
   */
  
  public function render($name, $value, $options, $attributes, $errors)
  {
    $attributes = array_merge($attributes, $options);
    $attributes = array_merge($attributes, array('name' => $name));
  
    // TBB: a sitewide additional config settings file is used, if it
    // exists and a different one has not been explicitly specified.
    // However CKEditor can do more at the PHP level so you might not
    // really need this
    if (!isset($attributes['config']))
    {
      if (file_exists(sfConfig::get('sf_web_dir') . '/js/ckextraconfig.js'))
      {
        $attributes['config'] = '/js/ckextraconfig.js'; 
      }
    }

  
    // Merged in from Symfony 1.3's FCK rich text editor implementation,
    // since that is no longer available in 1.4
  
    $options = $attributes;

    // sf_web_dir already contains the relative root, don't append it twice
    $php_file = sfConfig::get('sf_plugins_dir') . '/apostropheCkEditorPlugin/web/js/ckeditor/ckeditor.php';

    // If you override this to add toolbars, you must also reproduce the settings here
    // for the existing toolbars
    $toolbars = sfConfig::get('app_a_ckEditor_toolbars', 
      array(
        'Default' => array(
          array('Format', 'Bold', 'Italic', 'Blockquote'),
          array('NumberedList','BulletedList','-','Link','Unlink','Anchor','-','Table',
        '-','FitWindow', 'Source')),
        'Main' => array(
            array('Format', 'Bold', 'Italic', 'Blockquote'),
            array('NumberedList','BulletedList','-','Link','Unlink','Anchor','-','Table','-','FitWindow','Source')),
        'Sidebar' => array(
            array('Format', 'Bold', 'Italic', 'Blockquote'),
            array('NumberedList','BulletedList','-','Link','Unlink','Anchor'),
            array('Source')),
        'Media' => array(
            array('Bold', 'Italic', '-','Link', 'Unlink','Anchor', '-', 'Source'))));
    if (isset($toolbars[$options['tool']]))
    {
      $toolbar = $toolbars[$options['tool']];
    }
    else
    {
      $toolbar = $toolbars['Default'];
    }
    
    require_once($php_file);

    // ckEditor has the same "name and id are assumed to be the same" problem as fckEditor ):
    $ckEditor = new CKEditor();
    $ckEditor->returnOutput = true;
    // ckEditor's "find myself and bring myself in via a script tag" support is confused by Symfony's layout,
    // so we do it ourselves in view.yml (for now; we'll do it better)
    $ckEditor->initialized = true;
    
    // We have to clobber the editor if CKEditor thinks it already exists. It would be nice to reuse it
    // but we already pulled it out of the DOM to work with Apostrophe's refresh model for edit views.
    // http://stackoverflow.com/questions/2310111/uncaught-ckeditor-editor-the-instance-html-already-exists

    if (isset($options['width']))
    {
      $ckEditor->config['width'] = $options['width'];
    }
    elseif (isset($options['cols']))
    {
      $ckEditor->config['width'] = (string)((int) $options['cols'] * 10).'px';
    }

    if (isset($options['height']))
    {
      $ckEditor->config['height'] = $options['height'];
    }
    elseif (isset($options['rows']))
    {
      $ckEditor->config['height'] = (string)((int) $options['rows'] * 10).'px';
    }

    $ckEditor->config['toolbar'] = $toolbar;
    $ckEditor->config['format_tags'] = 'p;h3;h4;h5;h6;code';

    $content = "<script type=\"text/javascript\" charset=\"utf-8\">if (CKEDITOR.instances['$name']) { delete CKEDITOR.instances['$name'] };</script>";
    $content .= $ckEditor->editor($options['name'], $value);

    // Skip the braindead 'type='text'' hack that breaks Safari
    // in 1.0 compat mode, since we're in a 1.2+ widget here for sure

    // We must register an a.onSubmit handler to be sure of updating the
    // hidden field when a richtext slot or other AJAX context is updated
    $content .= <<<EOM
<script type="text/javascript" charset="utf-8">
$(function() {
  // The hidden textarea has no id, we have to go by name
  var textarea = $('[name=$name]');
  textarea.addClass('a-needs-update');
  textarea.bind('a.update', function() {
    apostrophe.log("Well we got this far");
    var value = CKEDITOR.instances['$name'].getData();
    apostrophe.log(value);
    textarea.val(value);
  });
});
</script>
EOM
;    
    return $content;
  }
}