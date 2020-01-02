window.onerror = (message, source, lineno, colno, error) ->
  alert """#{message}
  Line: #{lineno}:#{colno}
  Source: #{source}
  Error: #{error}
  """
  return
