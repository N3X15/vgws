
EMessageType =
  ERROR: 1
  WARNING: 2
  INFO: 0
class Message
  constructor: (@Type, @Message, @File='', @Line=0) ->
    @TypeName = switch @Type
      when EMessageType.INFO
        'INFO'
      when EMessageType.WARNING
        'WARNING'
      when EMessageType.ERROR
        'ERROR'

  display: (cmi) ->
    text = "#{@Type} in #{@File}:#{@Line}: #{@Message}"
    switch @Type
      when EMessageType.INFO
        cmi.Log.Info text
        cmi.addInfoMessage text
      when EMessageType.WARNING
        cmi.Log.Warn text
        cmi.addWarnMessage text
      when EMessageType.ERROR
        cmi.Log.Error text
        cmi.addErrorMessage text
