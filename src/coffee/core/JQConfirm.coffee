# From ChanMan, another project of mine.
JQConfirm = (config) ->
  if !config.title
    config.title='Confirm'
  config.yes_text = if 'yes_text' of config then config.yes_text else 'OK'
  config.no_text = if 'no_text' of config then config.no_text else 'Cancel'
  btns = {}
  if config.yes_text
    btns[config.yes_text] = (ui, e) ->
      if config.yes
        config.yes(ui, e)
      $(this).dialog('close')
  if config.no_text
    btns[config.no_text] = (ui, e) ->
      if config.no
        config.no(ui, e)
      $(this).dialog('close')
  $('<div></div>')
    .attr('title', config.title)
    .append($('<p></p>').html(config.text))
    .dialog
      resizable: false
      modal: true
      height: "auto"
      close: (ui, e) ->
        $(this).remove()
      buttons: btns
