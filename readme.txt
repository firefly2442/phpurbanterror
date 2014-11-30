--------------------------
Urban Terror PHP Stats
--------------------------

https://github.com/firefly2442/phpurbanterror/

This program allows you to monitor an Urban Terror (http://www.urbanterror.info) server via the scripting
capabilities of PHP.  It shows information about the server
settings as well as who is playing.  The code for querying
the server was adapted from systats:

http://systats.sourceforge.net

License: GPLv2 (see license.txt)


-Installation-

This is pretty easy, just copy the folder into your root
web directory or wherever you want it to reside.  Then edit
the "index.php" file and change the host, port, and website.

Fire up your web-browser and navigate to the directory that
you installed it.  It should pop up with information on the
server.  If not, check to make sure that the host and port
are correct.

If you want to add your own custom 3rd party map images go
into the .pk3 file and find the levelshots folder.  Grab
the .jpg file for that map and put it in the images folder.
Make sure the image dimensions are not too large.
The script should be able to figure out the mapname and look
for the corresponding picture.

If you don't like the color scheme you can go into the
stylesheets folder and edit the default.css file.  There
are many guides online for choosing html compliant colors.

If you want PHP to display ALL warnings/errors/notices,
add ?devmode=1 to the URL.  For example:
http://yourwebsite.com/phpurbanterror/index.php?devmode=1

