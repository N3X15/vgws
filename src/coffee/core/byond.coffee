# A shitload of crutches for BYOND
window.onerror = (message, source, lineno, colno, error) ->
  if window.CLIENT
    toByond
      src:        window.CLIENT
      error:      message
      source:     source
      lineno:     lineno
      colno:      colno
      user_agent: navigator.userAgent
      platform:   navigator.platform

  alert """#{message}
  Source: #{source}
  Line: #{lineno}:#{colno}"""
  return

export fmtBYONDQuery = (query) ->
  o = ''
  if Object.keys(query).length
    i = 0
    for k, v of query
      o += if i++ == 0 then '?' else ';'
      o += encodeURIComponent(k) + '=' + encodeURIComponent(v)
  return o

export toByond = (query) ->
  uri = "byond://"+fmtBYONDQuery(query)
  if window.query and not window.query.has 'TEST'
    window.location = uri
  else
    console and console.log and console.log "toByond: Would have sent #{uri}", query
  return
