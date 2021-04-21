var range = $('.input-range'),
  value = $('.range-value');

value.html(range.attr('value') + ' rating');

range.on('input', function() {
  value.html(this.value + ' rating');
});