Description
===========
BibTeX plugin allows to add bibliography entries in a wordpress blog, supporting BibTeX style.


Revision
===========

Addition of multiple key=value pairs in the tag, mainly to support filtering for the YEAR
together with a main selection criteria.
Criteria are used in AND.

Features
===========

* input data directly from the bibtex text file
* easy inclusion in wordpress pages/posts by means of a dedicated tag
* possibility of filtering the bibtex entries based on their type (allow, deny)
* possibility to access the single bibtex entry source code to enable copy&paste (toggle-enabled visualization)
* expose URL of each document (if network-reachable)
* manage authors as single entity for further reference in the wordpress blog/pages

Compatibility
===========
The bibtex plugin has been developed and tested under Wordpress version 3.3.1. 

Author's naming convention
===========

* First
* Middle
* Last

If Lastname consists of several words, keep them together by using the ~ char (e.g., Danny De~Vito)
If the Firstname has two separate words and both are firstname and not first and middle name, then use
the ~ char to keep them together as one (e.g. Li~Cheng Wong).


A brief Markdown Example
===========

When writing a page/post, you can use the tag [bibtex] as follows:

This is my whole list of publications: [bibtex]
If you want to filter the type of bibtex items, you can use one of the attributes 
allow, deny and cite, keyword and category as follows:

This is my list of journal articles (refer to the supported reference types, below):
[bibtex allow=article]

This is my list of conference articles:
[bibtex allow=inproceedings]

This is my list of publications that are not technical reports:
[bibtex deny=techreport]

This is my latest conference paper:
[bibtex cite=CGW2006]

This is the list of publications that are deliverable (category of the publication)
[bibtex category=deliverable]

This is the list of publications that include Design Space Exploration in the keywords
[bibtex keyword=Design Space Exploration]

This is the list of publications for a given year
[bibtex year=2010]

This is the list of publications of author xxxxx
[bibtex author=xxxxx]

This is the list of the last 4 publications with respect to year and month date
of publication
[bibtex lates=4]

Supported BibTeX reference types (to be used in the pattern for retrieving publications)
===========


* Conference/Symposium/Workshop proceedings: inproceedings
* Journal article: article
* Book chapter: inbook
* Book: book
* Booklet: booklet
* Collection: incollection
* Proceedings editing: proceedings
* Reference manual: manual
* Master thesis: masterthesis
* PhD thesis: phdthesis
* Technical report, Deliverable: techreport
* Miscellanea: misc

Acknowledges
===========

This plugin adapts the well known Joomla BibTex plugin developed by Mark Austin (mark.austin@everythingthatiknowabout.com - www.everythingthatiknowabout.com) to Wordpress environment