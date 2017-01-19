# autobump
A simple Autobot to bump your inactive threads once in 24 hours on [BitcoinTalk](http://bitcointalk.org). I have seen some service providers([Decepticons](https://en.wikipedia.org/wiki/Decepticon)) on BitcoinTalk who provide this service. But, Personally, I think the costs of those services are not worth for the actual service. It is very simple bot and why not to give for free?

 So does this repo born. An opensource to auto-bump your inactive threads. If you can contribute, well, you're always welcome to the community ;)

 Oh, here is my BitcoinTalk link to [contact me](https://bitcointalk.org/index.php?action=profile;u=558835). Let me know what you think ;D


## Get Started


 1. Download/Clone this repo.
 2. Configure data
 3. Add Threads
 4. Add cron on your server to run cron.php with duration on your preference.

## Version

Well. Let's say  this is version 0.0, shall we? That's bad, what happened to professionalism?

 So,
 1. 0.1(beta)
 2. 0.2(beta)

## Configure Data
 All configure-able data are available in **data.json** file.

 Sample config looks like
 ```
  {
      "file": "data.json",
      "settings": {
          "username": "bitcointalk username",
          "password": "base64 encoded password",
          "cookieLength": "-1",
          "msgToPost": "BUMP"
      },
      "messages": {
        "isFolder":true|false,
        "folder":"Absolute path of the folder of text files",
        "texts":[
         "Message 1",
         "Message 2",
         "Message 3",
         "Message 4"
        ]
      },
      "threads": [{
          "url": "url to your thread"
      }]
  }
 ```

### Username and Password

 Under **settings** add you __username__ and __password__ to your [BitcoinTalk](https:bitcointalk.org) account. And beware, you must add your password as **Base64 Encoded**. You can do that [here](https://www.base64decode.org/)!

### Threads

 I think you have guessed how to add threads to **data.json** file.

 No? FINE

 Each thread should be a json object, like:
 ```
 {
  "url":"url to your thread"
 }
 ```
 Add this thread object to the **threads array** on data.json file.
 ```
 "threads": [
  {
    "url": "Your thread URL"
  },{
    "url": "Your second thread URL"
  }
 ]
 ```

## Message to Post on Thread
 From the version **0.2**, you can add Custom Message to post to the thread. Don't worry if you have older version data file, it'll defaultly take the **BUMP** message from the Settings object. Well, if you want some custom message to post to your thread instead of BUMP, you can easily do that in previous version too, by using ___"msgToPost"___ variable available in ___"settings"___ object. But, you can give only string. I just think, it'd be cool if you can post some different message to different thread of yours. It's boring to post same message to all threads, eh! BUMP!! BUMP!! BUMP!! aaaaahh. ** Alright, let's get to the point.**

 ```
 "messages": {
  "isFolder":true|false,
  "folder":"Absolute path of the folder of text files",
  "texts":[
   "Message 1",
   "Message 2",
   "Message 3",
   "Message 4"
   ]
 }
 ```
> _"isFolder"_:
> can contain either TRUE or FALSE. Tells the system whether text is available as files in a folder.
>
> _"folder"_:
> Absolute path of the folder where all files exist.
>
> _"texts"_:
> This is a backup system. If folder fails or you don't want to create numerous files with large texts, you can use
> this function to provide a array of string with messages.

Okay. The system works like:

 1. It checks for the ___"isFolder"___ for true. If it's true, then read the files from the given ___"folder"___.
 2. If above condition is false, then it'll look into ___"texts"___ array to fetch all give strings(messages).
 3. If above two condition fails, it'll take the default message ___"msgToPost"___ from ___"settings"___ object.

>
>_From  the available options taken above, system will take any one randomly to post reply to the given thread._
>

## Adding Cron Job
 Adding cron job differes on each Operating System and Hosting providers.

* [__Ubuntu__](http://askubuntu.com/questions/2368/how-do-i-set-up-a-cron-job)
* [__Windows 7__](https://technet.microsoft.com/en-us/library/cc748993\(v=ws.11\).aspx)
* [__GoDaddy__](https://technet.microsoft.com/en-us/library/cc748993\(v=ws.11\).aspx)
* [__CPanel__](https://confluence2.cpanel.net/display/ALD/Cron+Jobs)

 Aah.. you get the point and search according to your situation.

## License
This repo is being provided under [MIT](https://en.wikipedia.org/wiki/MIT_License) and you can find the [License](https://github.com/buxlover/autobump/blob/master/LICENSE) here.

## Donation!
 I think you forgot what you read up! This is **opensource**. Well, Donation? Sorry, **__NOT ACCEPTED__**(hehe).

## Credits
 I'll add all people who contributed to this repo.
 * [BuxLover](https://bitcointalk.org/index.php?action=profile;u=558835)(aah! That's me)
 * [Carlo Denaro](https://github.com/blackout314) ([Commits](https://github.com/buxlover/autobump/commits?author=blackout314))
