# php-mjpeg
PHP to create a MJPEG output

## Needs
3 containers for Redis Guestbook @ https://kubernetes.io/docs/tutorials/stateless-application/guestbook/
(this repo has a docker-compose.yaml that should work for docker)
(or just a PHP container, and you have to install the redis or a way to store JPG files)

## Explanation
Redis guestbook is a PHP sample from Google to connect to Redis, an in-memory database.  By using their images,
I skip needing to configure PHP, redis, or installing the redis client.
The PHP takes 60 jpg, and turns them into a MJPEG.  It stores each JPG in redis.
Each JPG frame needs to be uploaded individually, with ?cmd=set&key=<any letter representing different MJPEG>&value=<base64 of jpg data>, in a POST submission.
To see last JPG frame uploaded, use ?cmd=set&key=<same letter representing above MJPEG>, in a GET submission.
To see MJPEG, of images uploaded in order, use ?cmd=mjpeg&key=<same letter representing above MJPEG>, in a GET submission.
The MJPEG feed only lasts 3600 seconds.

## Additional values
You don't need redis.  You can upload your own images and save it to session.  You can modify it anyway you want.  It is simple enough.
MJPEG is a very very old technology.  I don't know how much longer, browsers will support it.  But it is free, and a lot of free software like Linux motion uses it.

## Usage: uploading JPG, for each frame
You can upload JPG images with curl
```
curl -H "Content-type: application/x-www-form-urlencoded" -X POST --data cmd=set --data key=A --data-urlencode value@IMG.b64  http://<IP>:<port>/img.php
```
It will store 60.

## Usage: see last uploaded JPG
You can view JPG image in your browser
```
http://<IP>:<port>/img.php?cmd=get&key=A
```

## Usage: see mjpeg video in browser
You can upload JPG images with curl
```
http://<IP>:<port>/img.php?cmd=mjpeg&key=A
```
To embed in a html file
```
<img src="http://<IP>:<port>/img.php?cmd=mjpeg&key=A">
```
I will only show a video with last 60 uploads, BUT it will continue streaming waiting for another upload (ie. using curl above).  So if you send a image every minute, a person receiving the stream on their browser with the URL, will receive the last uploaded jpg automatically.
This PHP and URL only will last 60min, then abort.  You can remove this for your own purposes.  But bandwidth over the internet costs $.

## Operational Sample
I have this in AWS.  Don't abuse.  AWS cost me $ with every gigabyte.
```
http://100.24.142.145:10000/img.php?cmd=mjpeg&key=E
```
It isn't a livestream, but it could.
