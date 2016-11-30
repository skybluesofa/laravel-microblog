# Laravel 5 Microblog

Create a microblogging platform (e.g., Twitter, Tumblr).

## Installation

First, install the package through Composer.

```php
composer require skybluesofa/laravel-microblog
```

Then include the service provider inside `config/app.php`.

```php
'providers' => [
    ...
    Skybluesofa\Microblog\ServiceProvider::class,
    ...
];
```
Publish config and migrations

```
php artisan vendor:publish --provider="Skybluesofa\Microblog\ServiceProvider"
```
Configure the published config in
```
config\microblog.php
```
Finally, migrate the database
```
php artisan migrate
```

## Add Authorship to a User
When a User is a MicroblogAuthor, they can create blog posts.
```php
use Skybluesofa\Microblog\Model\Traits\MicroblogAuthor;
class User extends Model
{
    use MicroblogAuthor;
    ...
}
```

## Add Blog Friends to a User
Blog Friends limits who can see a User's blog posts
```php
use Skybluesofa\Microblog\Model\Traits\MicroblogAuthor;
class User extends Model
{
    use MicroblogAuthor;
    ...
}
```

## How to use
[Check the Test file to see the package in action](https://github.com/skybluesofa/laravel-microblog/blob/master/tests/MicroblogPostTest.php)

### Blog posts

#### Create a blog post
```php
$post = new Post;
$post->content = 'This is the story of my life';
$user->savePost($post);
```

#### Delete a blog post
```php
$post->delete();
```

#### Publish a blog post (move from draft to published status)
```php
$post->publish();
```

#### Unpublish a blog post (move from published to draft status)
```php
$post->unpublish();
```

#### Make a post visible to friends
```php
$post->share();
```
or
```php
$post->shareWithFriends();
```

#### Make a post visible to everyone who has the URL
```php
$post->shareWithEveryone();
```

## Contributing
See the [CONTRIBUTING](CONTRIBUTING.md) guide.
