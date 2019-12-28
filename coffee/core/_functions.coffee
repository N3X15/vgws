# await sleep ...
sleep = (ms) ->
  return new Promise(resolve => setTimeout(resolve, ms))

# Generate a new UUID v4
uuidv4 = ->
  ([ 1e7 ] + -1e3 + -4e3 + -8e3 + -1e11).replace /[018]/g, (c) ->
    (c ^ crypto.getRandomValues(new Uint8Array(1))[0] & 15 >> c / 4).toString 16

# Polyfill for String::format
if ! String::format
  String::format = ->
    args = arguments
    @replace /{(\d+)}/g, (match, number) ->
      if typeof args[number] != 'undefined' then args[number] else match

# [].contains(thing)
Array::contains = (elem) ->
  @indexOf(elem) isnt - 1

# [].remove(thing)
Array::remove = (elem) ->
  @filter (e) ->
    e != elem

# class blah:
#   @property 'name',
#     get: -> return 1
#     set: (newval) ->
#       @whatever = newval
Function::property = (prop, desc) ->
  Object.defineProperty @prototype, prop, desc

Function::getter = (prop, get) ->
  Object.defineProperty @prototype, prop, {get, configurable: yes}

Function::setter = (prop, set) ->
  Object.defineProperty @prototype, prop, {set, configurable: yes}

Array::removeItem = (item) ->
  return @splice @indexOf(item), 1

# Doesn't seem to work anymore.
VS_NO_EMOJI = "\uFE0E"
disable_emoji_rendering = (char)->
  return VS_NO_EMOJI+char

numberWithCommas = (x) ->
  parts = x.toString().split('.')
  parts[0] = parts[0].replace(/\B(?=(\d{3})+(?!\d))/g, ',')
  parts.join '.'
