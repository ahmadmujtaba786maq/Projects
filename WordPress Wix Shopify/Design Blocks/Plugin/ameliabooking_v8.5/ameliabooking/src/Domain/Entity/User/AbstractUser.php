<?php

namespace AmeliaBooking\Domain\Entity\User;

use AmeliaBooking\Domain\ValueObjects\DateTime\Birthday;
use AmeliaBooking\Domain\ValueObjects\Json;
use AmeliaBooking\Domain\ValueObjects\Number\Integer\Id;
use AmeliaBooking\Domain\ValueObjects\Picture;
use AmeliaBooking\Domain\ValueObjects\String\Description;
use AmeliaBooking\Domain\ValueObjects\String\Email;
use AmeliaBooking\Domain\ValueObjects\String\Name;
use AmeliaBooking\Domain\ValueObjects\String\Password;
use AmeliaBooking\Domain\ValueObjects\String\Phone;
use AmeliaBooking\Domain\ValueObjects\String\Status;

/**
 * Class AbstractUser
 *
 * @package AmeliaBooking\Domain\Entity\User
 *
 */
abstract class AbstractUser
{
    public const USER_ROLE_ADMIN    = 'admin';
    public const USER_ROLE_PROVIDER = 'provider';
    public const USER_ROLE_MANAGER  = 'manager';
    public const USER_ROLE_CUSTOMER = 'customer';

    /** @var Description */
    private $note;

    /** @var Id */
    private $id;

    /** @var Status */
    private $status;

    /** @var Name */
    protected $firstName;

    /** @var Name */
    protected $lastName;

    /** @var Birthday */
    protected $birthday;

    /** @var Picture */
    protected $picture;

    /** @var Id */
    protected $externalId;

    /** @var Email */
    protected $email;

    /** @var Phone */
    protected $phone;

    /** @var Password */
    private $password;

    /** @var Json */
    private $usedTokens;

    /** @var int */
    private $loginType;

    /** @var Name */
    private $zoomUserId;

    /** @var Name */
    private $countryPhoneIso;

    /** @var  Json */
    protected $translations;

    /** @var  Json */
    protected $customFields;

    /** @var Name */
    private $appleCalendarId;

    /**
     * AbstractUser constructor.
     *
     * @param Name  $firstName
     * @param Name  $lastName
     * @param Email $email
     */
    public function __construct(
        Name $firstName,
        Name $lastName,
        Email $email
    ) {
        $this->firstName = $firstName;
        $this->lastName  = $lastName;
        $this->email     = $email;
    }

    /**
     * @return Id
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param Id $id
     */
    public function setId(Id $id)
    {
        $this->id = $id;
    }

    /**
     * @return Status
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * @param Status $status
     */
    public function setStatus(Status $status)
    {
        $this->status = $status;
    }

    /**
     * @return Name
     */
    public function getFirstName()
    {
        return $this->firstName;
    }

    /**
     * @param Name $firstName
     */
    public function setFirstName(Name $firstName)
    {
        $this->firstName = $firstName;
    }

    /**
     * @return Name
     */
    public function getLastName()
    {
        return $this->lastName;
    }

    /**
     * @param Name $lastName
     */
    public function setLastName(Name $lastName)
    {
        $this->lastName = $lastName;
    }

    /**
     * @return string
     */
    public function getFullName()
    {
        return $this->firstName->getValue() . ' ' . $this->lastName->getValue();
    }

    /**
     * @return Birthday
     */
    public function getBirthday()
    {
        return $this->birthday;
    }

    /**
     * @param Birthday $birthday
     */
    public function setBirthday(Birthday $birthday)
    {
        $this->birthday = $birthday;
    }

    /**
     * @return Picture
     */
    public function getPicture()
    {
        return $this->picture;
    }

    /**
     * @param Picture $picture
     */
    public function setPicture(Picture $picture)
    {
        $this->picture = $picture;
    }

    /**
     * @return ID
     */
    public function getExternalId()
    {
        return $this->externalId;
    }

    /**
     * @param ID $externalId
     */
    public function setExternalId(Id $externalId)
    {
        $this->externalId = $externalId;
    }

    /**
     * @return Email
     */
    public function getEmail()
    {
        return $this->email;
    }

    /**
     * @param Email $email
     */
    public function setEmail(Email $email)
    {
        $this->email = $email;
    }

    /**
     * @return Phone
     */
    public function getPhone()
    {
        return $this->phone;
    }

    /**
     * @param Phone $phone
     */
    public function setPhone(Phone $phone)
    {
        $this->phone = $phone;
    }

    /**
     * Get the user type in a string form
     */
    public function getType()
    {
        return self::USER_ROLE_CUSTOMER;
    }

    /**
     * @return Description
     */
    public function getNote()
    {
        return $this->note;
    }

    /**
     * @param Description $note
     */
    public function setNote(Description $note)
    {
        $this->note = $note;
    }

    /**
     * @return Name
     */
    public function getZoomUserId()
    {
        return $this->zoomUserId;
    }

    /**
     * @param Name $zoomUserId
     */
    public function setZoomUserId(Name $zoomUserId)
    {
        $this->zoomUserId = $zoomUserId;
    }

    /**
     * @return Password
     */
    public function getPassword()
    {
        return $this->password;
    }

    /**
     * @param Password $password
     */
    public function setPassword($password)
    {
        $this->password = $password;
    }

    /**
     * @return Json
     */
    public function getUsedTokens()
    {
        return $this->usedTokens;
    }

    /**
     * @param Json $usedTokens
     */
    public function setUsedTokens($usedTokens)
    {
        $this->usedTokens = $usedTokens;
    }

    /**
     * @return int
     */
    public function getLoginType()
    {
        return $this->loginType;
    }

    /**
     * @param int $loginType
     */
    public function setLoginType($loginType)
    {
        $this->loginType = $loginType;
    }

    /**
     * @return Name
     */
    public function getCountryPhoneIso()
    {
        return $this->countryPhoneIso;
    }

    /**
     * @param Name $countryPhoneIso
     */
    public function setCountryPhoneIso(Name $countryPhoneIso)
    {
        $this->countryPhoneIso = $countryPhoneIso;
    }

    /**
     * @return Json
     */
    public function getTranslations()
    {
        return $this->translations;
    }

    /**
     * @param Json $translations
     */
    public function setTranslations(Json $translations)
    {
        $this->translations = $translations;
    }

    /**
     * @return Json
     */
    public function getCustomFields()
    {
        return $this->customFields;
    }

    /**
     * @param Json $customFields
     */
    public function setCustomFields(Json $customFields)
    {
        $this->customFields = $customFields;
    }


    /**
     * @return Name
     */
    public function getAppleCalendarId()
    {
        return $this->appleCalendarId;
    }

    /**
     * @param Name $appleCalendarId
     */
    public function setAppleCalendarId(Name $appleCalendarId)
    {
        $this->appleCalendarId = $appleCalendarId;
    }

    /**
     * @return array
     */
    public function toArray()
    {
        return [
            'id'               => null !== $this->getId() ? $this->getId()->getValue() : null,
            'firstName'        => $this->getFirstName()->getValue(),
            'lastName'         => $this->getLastName()->getValue(),
            'birthday'         => null !== $this->getBirthday() ? $this->getBirthday()->getValue() : null,
            'email'            => $this->getEmail() ? $this->getEmail()->getValue() : null,
            'phone'            => null !== $this->getPhone() ? $this->getPhone()->getValue() : null,
            'type'             => $this->getType(),
            'status'           => null !== $this->getStatus() ? $this->getStatus()->getValue() : null,
            'note'             => null !== $this->getNote() ? $this->getNote()->getValue() : null,
            'zoomUserId'       => null !== $this->getZoomUserId() ? $this->getZoomUserId()->getValue() : null,
            'countryPhoneIso'  => null !== $this->getCountryPhoneIso() ? $this->getCountryPhoneIso()->getValue() : null,
            'externalId'       => null !== $this->getExternalId() ? $this->getExternalId()->getValue() : null,
            'pictureFullPath'  => null !== $this->getPicture() ? $this->getPicture()->getFullPath() : null,
            'pictureThumbPath' => null !== $this->getPicture() ? $this->getPicture()->getThumbPath() : null,
            'translations'     => $this->getTranslations() ? $this->getTranslations()->getValue() : null,
            'customFields'     => $this->getCustomFields() ? $this->getCustomFields()->getValue() : null,
            'appleCalendarId'  => null !== $this->getAppleCalendarId() ? $this->getAppleCalendarId()->getValue() : null,
//            'password'         => null !== $this->getPassword() ? $this->getPassword()->getValue() : null
        ];
    }
}
