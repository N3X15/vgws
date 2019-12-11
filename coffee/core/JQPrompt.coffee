JQPrompt = (config) ->
  if !config.title
    config.title='Prompt'
  config.value = if 'value' of config then config.value else ''
  config.ok_text = if 'ok_text' of config then config.ok_text else 'OK'
  config.cancel_text = if 'cancel_text' of config then config.cancel_text else 'Cancel'
  btns = {}
  btns[config.ok_text] = (ui, e) ->
    if config.ok
      config.ok(ui, e, $('#jqconfirm-input').val())
    $(this).dialog('close')
  btns[config.cancel_text] = (ui, e) ->
    if config.cancel
      config.cancel(ui, e, $('#jqconfirm-input').val())
    $(this).dialog('close')
  $('<div></div>')
    .attr('title', config.title)
    .append($('<p></p>').html(config.text))
    .append($('<input id="jqconfirm-input" type="textbox">').val(config.value))
    .dialog
      resizable: false
      modal: true
      height: "auto"
      close: (ui, e) ->
        $(this).remove()
      buttons: btns
