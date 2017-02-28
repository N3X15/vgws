$Options=null
nOpts=0
addNewOption = (optID, optText) ->
    row = $("<div class='option' data-opt-id='#{optID}'></div>")
    row.append $('<label>').text("##{optID}")
    row.append $("<input type='textbox'></input>").val(optText)
    row.append $("<span class='glyphicon glyphicon-trash cmdRemOption' data-opt-id='#{optID}'></input>").val(optText)
    row.insertBefore $Options
loadPollOptions = () ->
    $.ajax(
      type : 'POST'
      url : AJAX_URI
      data :
        ajax : 1
        act : 'getpoll_o'
        pollID : POLL_ID
    ).done (response) =>
      if response["status"] == false
        alert("Server returned error message:\n"+response["error"])
      else
        $('.option').remove()
        for own oid, opt of response.opts
          addNewOption opt.ID, opt.text
$ ->
  $Options=$('#optcontrols')
  $('#cmdAddOption').on 'click', (e) ->
    o_text = prompt 'Desired HTML for new option, or blank to cancel.', ''
    if o_text == ''
      return
    $.ajax(
      type : 'POST'
      url : AJAX_URI
      data :
        ajax : 1
        act : 'addpoll_o'
        text : o_text
        pollID : POLL_ID
    ).done (response) =>
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
      if response["status"] == false
        alert("Server returned error message:\n"+response["error"])
      else
        loadPollOptions()
  loadPollOptions()
