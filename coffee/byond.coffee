# A shitload of crutches for BYOND
window.onerror = (message, source, lineno, colno, error) ->
  if window.CLIENT
    toBYOND
      src: window.CLIENT
      error:      message
      source:     source
      lineno:     lineno
      colno:      colno
      user_agent: navigator.userAgent
  else
    alert """#{err.msg}
    Source: #{err.source}
    Line: #{err.lineno}:#{err.colno}"""
  return

fmtBYONDQuery = (query) ->
  o = ''
  if Object.keys(query).length
    i = 0
    for k, v of query
      o += if i++ == 0 then '?' else ';'
      o += encodeURIComponent(k) + '=' + encodeURIComponent(v)
  return o

toByond = (query) ->
  window.CLIENT and window.location = "byond://"+query
  return
