
$Options=null
nOpts=0

loadPollOptions = () ->
  # POST (?!) to our AJAX endpoint, with content {ajax:1,act:"getpoll_o",pollID:...}
  $.ajax
    type : 'POST'
    url : AJAX_URI
    data :
      ajax : 1
      act : 'getpoll_o'
      pollID : POLL_ID
  .done (response) =>
    if ('status' not of response) or response["status"] == false
      alert("Server returned error message:\n"+response["error"])
    else
      $('.option').remove()
      for own oid, opt of response.opts
        addNewOption opt.ID, opt.text
    return
  return

# Wait for document init
$ ->
  $Options=$('#optcontrols')
  if $Options.length > 0
    $('#cmdAddOption').on 'click', (e) ->
      o_text = prompt 'Desired HTML for new option, or blank to cancel.', ''
      if o_text == ''
        return
      $.ajax
        type: 'POST'
        url: AJAX_URI
        data:
          ajax: 1
          act: 'addpoll_o'
          text: o_text
          pollID: POLL_ID
      .done (response) =>
        if response["status"] == false
          alert("Server returned error message:\n"+response["error"])
        else
          loadPollOptions()

    $('.form').on 'click', '.cmdRemOption', (e) ->
      optID = $(@).attr('data-opt-id')
      if !confirm("Are you sure you want to delete poll option ##{optID}?")
        return
      $.ajax(
        type : 'POST'
        url : AJAX_URI
        data :
          ajax : 1
          act : 'rmpoll_o'
          pollID : POLL_ID
          'optID': optID
      ).done (response) =>
        if ('status' not of response) or response["status"] == false
          alert("Server returned error message:\n"+response["error"])
        else
          loadPollOptions()
        return
    loadPollOptions()
  return
