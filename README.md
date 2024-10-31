QuizMeister
===========

**Contributors:** dessimat0r

**Donate link:** https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=KXNX6FPVJ7KGG

**Tags:** quizzes, quiz, user, social, game, facebook, twitter, questions, answers

**Requires at least:** 3.0.1

**Tested up to:** 4.6.1

**Stable tag:** trunk

**License:** GPLv2 or later

**License URI:** http://www.gnu.org/licenses/gpl-2.0.html

QuizMeister allows users to author their own quiz posts with multiple answers per question, providing social sharing features and media embedding.

Description
-----------

QuizMeister provides social network share functionality for your quiz score (via Twitter, Facebook, etc.) with external media support (via Imgur, Pintrest, YouTube, Vimeo, etc.) on quiz questions, through oEmbed. The format is multiple questions, each with multiple answers. This brings additional value to your Wordpress blog or website, which can synergise with your existing content.

For external/social login support (Google, Facebook, etc) we suggest installing another plugin such as WP-OAuth. This functionality -may- be incorporated into later versions. The included template integrated into the plugin for displaying quizzes (but with the ability to be disabled) is most compatible with TwentyFifteen (included with Wordpress), but there is the possibility of creating your own overriding template.

Future versions will include bugfixes and feature enhancements.

Installation
------------

1. Upload the plugin files to the `/wp-content/plugins/quizmeister` directory, or install the plugin through the WordPress plugins screen directly.
2. Activate the plugin through the 'Plugins' screen in WordPress
3. Create a generic WordPress page from the admin section with `[quizmeister_new_quiz]` on it
4. Use the Settings->QuizMeister screen to configure the plugin, selecting the previously mentioned page as the 'New Quiz' page.
5. Reference the 'New Quiz' page with the page permalink anywhere on your WordPress installation - on posts, via side links, header links, anywhere. Otherwise users won't know they can create quizzes.
6. Install a plugin which allows users to create new accounts via their social media profiles (Facebook, Twitter, etc). WP-OAuth is good for this, and has been tested with this plugin.
7. For Facebook sharing support, you need to sign into Facebook, and create an application key, then paste it back into the QuizMeister settings.

Frequently Asked Questions
--------------------------
**What if users have no Javascript?**

We have tested basic support without Javascript, so it should work. However, users are limited to something like 4 questions with 4 answers each (max) in this situation. Otherwise we use Javascript for dynamic addition and removal of elements on the 'add new quiz' page. The number of questions and answers for non-Javascript users may be changed in the future or be configurable. Besides, most browsers support Javascript these days! ;)

**How many questions and answers per questions are supposed?**

In this version, 10 questions are supposed per quiz, with 5 answers per question.

**How is media supported?**

Each quiz has a featured image, then each question can have an oEmbed to provide media, which can be rich and fetched from an external site. Just provide the URL.

**How do users share their scores?**

There is a 'tweet' button at the bottom every time a user completes a quiz. This allows the user to share their score on Twitter. If the Facebook application key is configured, there is additionally a Facebook share button which does the same thing, but on Facebook.

**My theme looks weird with the plugin!**

This can sometimes be expected. We are most compatible with TwentyFifteen. We aim to provide other integrated templates in the future that match other popular WordPress themes and those included (more of a priority than external ones). However, you can disable the built-in template and use your own. Details are included in the source files, but you require WordPress know-how.

**What if users upload images that aren't cleaned up because they didn't complete quiz creation?**

There is a gallery clean-up scheduled job that can be activated from the settings page. This will clean up all the images which have been orphaned from quiz creation only, on a regular basis.

**Screenshots**
![Creating a quiz #1](https://raw.githubusercontent.com/Dessimat0r/QuizMeister/master/assets/screenshot-1.png)
![Creating a quiz #2](https://raw.githubusercontent.com/Dessimat0r/QuizMeister/master/assets/screenshot-2.png)
![Starting a quiz](https://raw.githubusercontent.com/Dessimat0r/QuizMeister/master/assets/screenshot-3.png)
![In the middle of a quiz](https://raw.githubusercontent.com/Dessimat0r/QuizMeister/master/assets/screenshot-4.png)
![Finishing a quiz](https://raw.githubusercontent.com/Dessimat0r/QuizMeister/master/assets/screenshot-5.png)
![The settings page](https://raw.githubusercontent.com/Dessimat0r/QuizMeister/master/assets/screenshot-6.png)

Changelog
---------
- 1.0.1
-- Fixed some input/output sanitisation issues, changed class/function/filter names to not be 2 character, not calling Wordpress externally from PHP file, better jQuery in New Quiz form, other cleanups...

 - 1.0
 -- Release version.

Upgrade Notice
--------------

N/A
