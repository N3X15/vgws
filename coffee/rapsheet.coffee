# Rapsheet stuff.
$ ->
  $('.jobs').tagit
    fieldName: 'jobs[]'
    availableTags: window.AUTOCOMPLETE

  $('button#getlast').click ->
    core.api_post ['findcid'], null, { ckey: $('#banCKey').val() }, (data, status) ->
      #alert("Returned: "+status);
      if status == 'success'
        rows = data.split('\\n')
        $('#banIP').val rows[0]
        $('#banCID').val rows[1]
      else
        alert 'Couldn\'t find that ckey.'
      return
    return
  return
