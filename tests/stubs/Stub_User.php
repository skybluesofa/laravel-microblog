<?php
namespace App;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Skybluesofa\Microblog\Model\Traits\MicroblogFriends;
use Skybluesofa\Microblog\Model\Traits\MicroblogAuthor;

class User extends Authenticatable
{
    use MicroblogFriends, MicroblogAuthor;

    public function getBlogFriends()
    {
        // Return null to get all users or an array to get only those users
        return $this->friendIds;
    }

    // The User::friendIds variable and User::setBlogFriends() method are meant to show
    // one way that you can set the value returned by the User->getBlogFriends() method.
    // Other ways would be to call a method on another class, hardcode the value into
    // the method, or use a trait to override the UserContract::getBlogFriends() method.
    protected $friendIds = null;
    public function setBlogFriends($friendIds = null) {
        $this->friendIds = $friendIds;
    }
}
