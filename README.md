Before running this make sure the database name, user and passwords etc is correct in all of the phps
Also make sure that the url links to php scripts and other webpages are correct for your setup, you might need to change them.
user 999 is reservered for scientist classifcations of unresolvable images
Secondly there needs to be 2 new tables photosate and xclassifications
photostate with photo_id as an (int) and the primary key, state as enum of values('new','in_circulation','unresolvable','classified'), evenness, fraction_blank and fraction_support as a float which can be null
and valid_classifications as an int that can be null
xclassifications as an autoincrement int and primary key, photo_id as an int, species as an int and number_of_individuals as an int