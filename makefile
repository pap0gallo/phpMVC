#start:
	#php -S localhost:8080 -t public public/L27/index.php
	#php -S localhost:8080 -t public index.php


PORT ?= 8000

start:
    php -S 0.0.0.0:$(PORT) -t public index.php