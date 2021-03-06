<?php
namespace App;

use Skybluesofa\Microblog\Model\User as MicroblogUser;
use Skybluesofa\Microblog\Model\Traits\MicroblogFriends;
use Skybluesofa\Microblog\Model\Traits\MicroblogAuthor;

class User extends MicroblogUser
{
    use MicroblogFriends, MicroblogAuthor;

    /** @var Array|null */
    protected $friendIds = null;

    public function getBlogFriends() : ?Array
    {
        // Return null to get all users or an array to get only those users
        return $this->friendIds;
    }

    // The User::friendIds variable and User::setBlogFriends() method are meant to show
    // one way that you can set the value returned by the User->getBlogFriends() method.
    // Other ways would be to call a method on another class, hardcode the value into
    // the method, or use a trait to override the UserContract::getBlogFriends() method.
    public function setBlogFriends(?Array $friendIds = null)
    {
        $this->friendIds = $friendIds;
    }
}
