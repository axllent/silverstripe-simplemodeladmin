# Simple ModelAdmin for SilverStripe 3

This is a "trimmed down" version of Silverstripe's ModelAdmin, the differences being:
* Import, Export & Print functionality has been removed
* Filtering has been removed
* By default, column sorting has been disabled

## Requirements
* SilverStripe 3.0+

## Usage
<pre>
class MyDataObject_ModelAdmin extends SimpleModelAdmin {}
</pre>