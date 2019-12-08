// Generated by CoffeeScript 1.12.4
var $Options, addNewOption, nOpts;

$Options = null;

nOpts = 0;

addNewOption = function(optText) {
  var row;
  nOpts++;
  row = $("<div class='option' data-opt-id='" + nOpts + "'></div>");
  row.append($('<label>').text("#" + nOpts));
  row.append($("<input type='textbox'></input>"));
  return row.insertBefore($Options);
};

$(function() {
  $Options = $('#options');
  return $('#cmdAddOption').on('click', function(e) {
    return addNewOption("");
  });
});

//# sourceMappingURL=data:application/json;base64,eyJ2ZXJzaW9uIjozLCJmaWxlIjoiZWRpdHBvbGwuanMiLCJzb3VyY2VSb290IjoiLi4vLi4iLCJzb3VyY2VzIjpbInRtcC9odGRvY3MvanMvZWRpdHBvbGwuY29mZmVlIl0sIm5hbWVzIjpbXSwibWFwcGluZ3MiOiI7QUFBQSxJQUFBOztBQUFBLFFBQUEsR0FBUzs7QUFDVCxLQUFBLEdBQU07O0FBQ04sWUFBQSxHQUFlLFNBQUMsT0FBRDtBQUNYLE1BQUE7RUFBQSxLQUFBO0VBQ0EsR0FBQSxHQUFNLENBQUEsQ0FBRSxtQ0FBQSxHQUFvQyxLQUFwQyxHQUEwQyxVQUE1QztFQUNOLEdBQUcsQ0FBQyxNQUFKLENBQVcsQ0FBQSxDQUFFLFNBQUYsQ0FBWSxDQUFDLElBQWIsQ0FBa0IsR0FBQSxHQUFJLEtBQXRCLENBQVg7RUFDQSxHQUFHLENBQUMsTUFBSixDQUFXLENBQUEsQ0FBRSxnQ0FBRixDQUFYO1NBQ0EsR0FBRyxDQUFDLFlBQUosQ0FBaUIsUUFBakI7QUFMVzs7QUFNZixDQUFBLENBQUUsU0FBQTtFQUNBLFFBQUEsR0FBUyxDQUFBLENBQUUsVUFBRjtTQUNULENBQUEsQ0FBRSxlQUFGLENBQWtCLENBQUMsRUFBbkIsQ0FBc0IsT0FBdEIsRUFBK0IsU0FBQyxDQUFEO1dBQzdCLFlBQUEsQ0FBYSxFQUFiO0VBRDZCLENBQS9CO0FBRkEsQ0FBRiIsInNvdXJjZXNDb250ZW50IjpbIiRPcHRpb25zPW51bGxcbm5PcHRzPTBcbmFkZE5ld09wdGlvbiA9IChvcHRUZXh0KSAtPlxuICAgIG5PcHRzKytcbiAgICByb3cgPSAkKFwiPGRpdiBjbGFzcz0nb3B0aW9uJyBkYXRhLW9wdC1pZD0nI3tuT3B0c30nPjwvZGl2PlwiKVxuICAgIHJvdy5hcHBlbmQgJCgnPGxhYmVsPicpLnRleHQoXCIjI3tuT3B0c31cIilcbiAgICByb3cuYXBwZW5kICQoXCI8aW5wdXQgdHlwZT0ndGV4dGJveCc+PC9pbnB1dD5cIilcbiAgICByb3cuaW5zZXJ0QmVmb3JlICRPcHRpb25zXG4kIC0+XG4gICRPcHRpb25zPSQoJyNvcHRpb25zJylcbiAgJCgnI2NtZEFkZE9wdGlvbicpLm9uICdjbGljaycsIChlKSAtPlxuICAgIGFkZE5ld09wdGlvbihcIlwiKVxuIl19
//# sourceURL=/host/ss13.nexisonline.net/tmp/htdocs/js/editpoll.coffee