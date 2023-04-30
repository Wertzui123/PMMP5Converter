# PMMP4 -> PMMP5 converter
This is a simple script that automatically converts parts of your PocketMine-MP 4 plugin to API 5.
<br>This can save you a lot of time and monotonous work.

**Attention:** This script is **not perfect** and **may introduce bugs**! Use at your own risk and always check the output (which often needs some manual conversion anyway).

## How to use?
Just run this script in a terminal and specify the path to your plugin's base directory:
```
php converter.php /path/to/your/plugin
```

## Things to be aware of
* This script only converts the most common things; you will still have to do some manual conversion.
* This script will not add any `use` statements, even though it may introduce the use of new classes; this will break your plugin if you don't add the `use` statements yourself.
* This script was written by me for my own plugins; it should work for most other plugins too, but it may not help you with some things.