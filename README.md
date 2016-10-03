# autobump
A simple Autobot to bump your inactive threads once in 24 hours on [BitcoinTalk](http://bitcointalk.org). I have seen some service providers([Decepticons](https://en.wikipedia.org/wiki/Decepticon)) on BitcoinTalk who provide this service. But, Personally, I think the costs of those services are not worth for the actual service. It is very simple bot and why not to give for free?

 So does this repo born. An opensource to auto-bump your inactive threads. If you can contribute, well, you're always welcome to the community ;)

 Oh, here is my BitcoinTalk link to [contact me](https://bitcointalk.org/index.php?action=profile;u=558835). Let me know what you think ;D


## Get Started


 1. Download/Clone this repo.
 2. Configure data
 3. Add Threads
 4. Add cron on your server to run process-new.php with duration on your preference.

## Version

Well. Let's say  this is version 0.0, shall we? That's bad, what happened to professionalism?

 So,
 1. 0.1(beta)

## Configure Data
 All configure-able data are available in **data.json** file.

 Sample config looks like
 ```
 {
 "file": "data.json",
 "settings": {
  "username": "buxlover",
  "password": "password",
  "cookieLength": "-1",
  "msgToPost": "BUMP"
 },

 ```

 Under **settings** add you __username__ and __password__ to your [BitcoinTalk](https:bitcointalk.org) account. And beware, you must add your password as **Base64 Encoded**. You can do that [here](https://www.base64decode.org/)!

## Add Threads
 I think you have guessed how to threads to **data.json** file.

 No? FINE

 Each thread should be a json object, like:
 ```
 {
  "url":"url to your thread"
 }
 ```
 Add this thread object to the **threads array** on data.json file.
 ```
 "threads": [{
 "url": "Your thread URL"
 },{
 "url": "Your second thread URL"
 }]
 }
 ```

## Adding Cron Job
 Adding cron job differes on each Operating System and Hosting providers.

[__Ubuntu__](http://askubuntu.com/questions/2368/how-do-i-set-up-a-cron-job)
 [__Windows 7__](https://technet.microsoft.com/en-us/library/cc748993\(v=ws.11\).aspx)
 [__GoDaddy__](https://technet.microsoft.com/en-us/library/cc748993\(v=ws.11\).aspx)
 [__CPanel__](https://confluence2.cpanel.net/display/ALD/Cron+Jobs)

 Aah.. you get the point and search according to your situation.

## License
This repo is being provided under [MIT](https://en.wikipedia.org/wiki/MIT_License) and you can find the [License](https://github.com/buxlover/autobump/blob/master/LICENSE) here.

## Donation!
 I think you forgot what you read up! This is **opensource**. Well, Donation? Sorry, **__NOT ACCEPTED__**(hehe).

## Credits
 I'll add all people who contributed to this repo.
 * [BuxLover](https://bitcointalk.org/index.php?action=profile;u=558835)(aah! That's me)
