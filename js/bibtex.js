  
var $j = jQuery.noConflict();
$j(document).ready(function()
	{
	  // Toggle Single Bibtex entry
	  $j('a.toggle').click(function()
		{   $j( $j(this).attr("href") ).toggle();
		    return false;
		});

	});


function toggleInfo(articleid) {

	var entry = document.getElementById(articleid);
	var bib = document.getElementById(articleid);

	// Get the abstracts/reviews/bibtext in the right location
	// in unsorted tables this is always the case, but in sorted tables it is necessary. 
	// Start moving in reverse order, so we get: entry, abstract,review,bibtex
	if (searchTable.className.indexOf('sortable') != -1) {
		if(bib) { entry.parentNode.insertBefore(bib,entry.nextSibling); }
		if(rev) { entry.parentNode.insertBefore(rev,entry.nextSibling); }
		if(abs) { entry.parentNode.insertBefore(abs,entry.nextSibling); }
	}

	if (bib) {
		if(bib.className.indexOf('bibtex') != -1) {
		bib.className.indexOf('noshow') == -1?bib.className = 'bibtex noshow':bib.className = 'bibtex';
		}		
	} else { 
		return;
	}
	
	// check if one or the other is available
	var bibshow = false;
	(bib && bib.className == 'bibtex')? bibshow = true: bibshow = false;
	
	// highlight original entry
	if(entry) {
		if (bibshow) {
		entry.className = 'entry highlight show';
		} else {
		entry.className = 'entry show';
		}		
	}
		
}
