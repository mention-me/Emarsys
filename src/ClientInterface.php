<?php

namespace Snowcap\Emarsys;

use Snowcap\Emarsys\Exception\ClientException;
use Snowcap\Emarsys\Exception\ServerException;

interface ClientInterface
{
    public const LAUNCH_STATUS_NOT_LAUNCHED = 0;

    public const LAUNCH_STATUS_IN_PROGRESS = 1;

    public const LAUNCH_STATUS_LAUNCHED_OR_SCHEDULED = 2;

    public const LAUNCH_STATUS_ERROR = 10;

    /**
     * @see https://dev.emarsys.com/v2/personalization/email-status-and-error-codes for codes
     */
    public const EMAIL_STATUS_CODE_ABORTED = -6;

    public const EMAIL_STATUS_CODE_PAUSED_ABORTED = -4;

    public const EMAIL_STATUS_CODE_LAUNCHED_PAUSED = -3;

    public const EMAIL_STATUS_CODE_TESTED_PAUSED = -2;

    public const EMAIL_STATUS_CODE_IN_DESIGN = 1;

    public const EMAIL_STATUS_CODE_TESTED = 2;

    public const EMAIL_STATUS_CODE_LAUNCHED = 3;

    public const EMAIL_STATUS_CODE_READY_TO_LAUNCH = 4;

    public const EMAIL_STATUS_CODE_NOT_LAUNCHED = 5;

    /**
     * @see https://dev.emarsys.com/docs/emarsys-api/b3A6MjQ4OTk4Njg-list-email-campaigns
     */
    public const CAMPAIGN_TYPE_ADHOC = 'adhoc';

    public const CAMPAIGN_TYPE_RECURRING = 'recurring';

    public const CAMPAIGN_TYPE_NEWSLETTER = 'newsletter';

    public const CAMPAIGN_TYPE_ON_EVENT = 'onevent';

    public const CAMPAIGN_TYPE_TEST_EMAIL = 'testemail';

    public const CAMPAIGN_TYPE_MULTILANGUAGE = 'multilanguage';

    public const CAMPAIGN_TYPE_BROADCAST = 'broadcast';

    /**
     * @see https://dev.emarsys.com/v2/response-codes where success is defined as zero
     */
    public const API_REPLY_CODE_SUCCESS = 0;

    /**
     * Add your custom fields mapping
     * This is useful if you want to use string identifiers instead of ids when you play with contacts fields
     *
     * Example:
     *  $mapping = [
     *      'myCustomField' => 7147,
     *      'myCustomField2' => 7148,
     *  ];
     */
    public function addFieldsMapping(array $mapping = []): void;

    /**
     * Add your custom field choices mapping
     * This is useful if you want to use string identifiers instead of ids when you play with contacts field choices
     *
     * Example:
     *  $mapping = [
     *      'myCustomField' => [
     *          'myCustomChoice' => 1,
     *          'myCustomChoice2' => 2,
     *      ]
     *  ];
     */
    public function addChoicesMapping(array $mapping = []): void;

    /**
     * Returns a field id from a field string_id (specified in the fields mapping)
     *
     *
     * @throws ClientException
     */
    public function getFieldId(string $fieldStringId): string|int;

    /**
     * Returns a field name from a field id (specified in the fields mapping) or the field id if no mapping is found
     *
     * @param string|int $fieldId
     *
     * @return string|int
     */
    public function getFieldStringId($fieldId);

    /**
     * Returns a choice id for a field from a choice name (specified in the choices mapping)
     *
     * @param string|int $fieldStringId
     * @param string|int $choice
     *
     * @throws ClientException
     */
    public function getChoiceId($fieldStringId, $choice): int;

    /**
     * Returns a choice name for a field from a choice id (specified in the choices mapping) or the choice id if no
     * mapping is found
     *
     * @param string|int $fieldId
     *
     * @return string|int
     * @throws ClientException
     */
    public function getChoiceName($fieldId, int $choiceId);

    /**
     * Returns a list of condition rules.
     *
     * @throws ClientException
     * @throws ServerException
     */
    public function getConditions(): Response;

    /**
     * Creates one or more new contacts/recipients.
     * Example :
     *  $data = array(
     *      'key_id' => '3',
     *      '3' => 'recipient@example.com',
     *      'source_id' => '123',
     *  );
     *
     *
     * @throws ClientException
     * @throws ServerException
     */
    public function createContact(array $data): Response;

    /**
     * Updates one or more contacts/recipients, identified by an external ID.
     *
     *
     * @throws ClientException
     * @throws ServerException
     */
    public function updateContact(array $data): Response;

    /**
     * Updates one or more contacts/recipients, identified by an external ID. If the contact does not exist in the
     * database, it is created.
     *
     *
     * @throws ClientException
     * @throws ServerException
     */
    public function updateContactAndCreateIfNotExists(array $data): Response;

    /**
     * Deletes a single contact/recipient, identified by an external ID.
     *
     *
     * @throws ClientException
     * @throws ServerException
     */
    public function deleteContact(array $data): Response;

    /**
     * Returns the internal ID of a contact specified by its external ID.
     *
     *
     * @throws ClientException
     * @throws ServerException
     */
    public function getContactId(string $fieldId, string $fieldValue): int;

    /**
     * Exports the selected fields of all contacts with properties changed in the time range specified.
     *
     *
     * @throws ClientException
     * @throws ServerException
     */
    public function getContactChanges(array $data): Response;

    /**
     * Returns the list of emails sent to the specified contacts.
     *
     *
     * @throws ClientException
     * @throws ServerException
     */
    public function getContactHistory(array $data): Response;

    /**
     * Returns all data associated with a contact.
     *
     * Example:
     *
     *  $data = array(
     *      'keyId' => 3, // Contact element used as a key to select the contacts.
     *                    // To use the internalID, pass "id" to the "keyId" parameter.
     *      'keyValues' => array('example@example.com', 'example2@example.com') // An array of contactIDs or values of
     *                                                                          // the column used to select contacts.
     *  );
     *
     *
     * @throws ClientException
     * @throws ServerException
     */
    public function getContactData(array $data): Response;

    /**
     * Exports the selected fields of all contacts which registered in the specified time range.
     *
     *
     * @throws ClientException
     * @throws ServerException
     */
    public function getContactRegistrations(array $data): Response;

    /**
     * Returns a list of contact lists which can be used as recipient source for the email.
     *
     *
     * @throws ClientException
     * @throws ServerException
     */
    public function getContactList(array $data): Response;

    /**
     * Creates a contact list which can be used as recipient source for the email.
     *
     *
     * @throws ClientException
     * @throws ServerException
     */
    public function createContactList(array $data): Response;

    /**
     * Deletes a contact list which can be used as recipient source for the email.
     *
     *
     * @throws ClientException
     * @throws ServerException
     */
    public function deleteContactList(string $listId): Response;

    /**
     * Creates a contact list which can be used as recipient source for the email.
     *
     *
     * @throws ClientException
     * @throws ServerException
     */
    public function addContactsToContactList(string $listId, array $data): Response;

    /**
     * This deletes contacts from the contact list which can be used as recipient source for the email.
     *
     *
     * @throws ClientException
     * @throws ServerException
     */
    public function removeContactsFromContactList(string $listId, array $data): Response;

    /**
     * Get a list of contact IDs that are in a contact list
     *
     *
     * @throws ClientException
     * @throws ServerException
     */
    public function getContactsFromContactList(string $listId, array $data): Response;

    /**
     * Checks whether a specific contact is included in the defined contact list.
     *
     *
     * @throws ClientException
     * @throws ServerException
     * @link http://documentation.emarsys.com/resource/developers/endpoints/contacts/check-a-contact-in-a-contact-list/
     */
    public function checkContactInList(int $contactId, int $listId): Response;

    /**
     * Returns a list of emails.
     *
     * @throws ClientException
     * @throws ServerException
     *
     * @link https://dev.emarsys.com/docs/emarsys-api/b3A6MjQ4OTk4Njg-list-email-campaigns
     */
    public function getEmails(?int $status = null, ?int $contactList = null, array $campaignTypes = []): Response;

    /**
     * Creates an email in eMarketing Suite and assigns it the respective parameters.
     * Example :
     *  $data = array(
     *      'language' => 'en',
     *      'name' => 'test api 010',
     *      'fromemail' => 'sender@example.com',
     *      'fromname' => 'sender email',
     *      'subject' => 'subject here',
     *      'email_category' => '17',
     *      'html_source' => '<html>Hello $First Name$,... </html>',
     *      'text_source' => 'email text',
     *      'segment' => 1121,
     *      'contactlist' => 0,
     *      'unsubscribe' => 1,
     *      'browse' => 0,
     *  );
     *
     * @throws ClientException
     * @throws ServerException
     */
    public function createEmail(array $data): Response;

    /**
     * Returns the attributes of an email and the personalized text and HTML source.
     *
     * @throws ClientException
     * @throws ServerException
     */
    public function getEmail(string $emailId, array $data): Response;

    /**
     * Launches an email. This is an asynchronous call, which returns 'OK' if the email is able to launch.
     *
     * @throws ClientException
     * @throws ServerException
     */
    public function launchEmail(string $emailId, array $data): Response;

    /**
     * Returns the HTML or text version of the email either as content type 'application/json' or 'text/html'.
     *
     * @throws ClientException
     * @throws ServerException
     */
    public function previewEmail(string $emailId, array $data): Response;

    /**
     * Returns the summary of the responses of a launched, paused, activated or deactivated email.
     *
     * @throws ClientException
     * @throws ServerException
     */
    public function getEmailResponseSummary(
        string $emailId,
        ?string $startDate = null,
        ?string $endDate = null,
        ?string $launchId = null
    ): Response;

    /**
     * Instructs the system to send a test email.
     *
     * @throws ClientException
     * @throws ServerException
     */
    public function sendEmailTest(string $emailId, array $data): Response;

    /**
     * Returns the URL to the online version of an email, provided it has been sent to the specified contact.
     *
     * @throws ClientException
     * @throws ServerException
     */
    public function getEmailUrl(string $emailId, array $data): Response;

    /**
     * Returns the delivery status of an email.
     *
     * @throws ClientException
     * @throws ServerException
     */
    public function getEmailDeliveryStatus(array $data): Response;

    /**
     * Lists all the launches of an email with ID, launch date and 'done' status.
     *
     * @throws ClientException
     * @throws ServerException
     */
    public function getEmailLaunches(array $data): Response;

    /**
     * Exports the selected fields of all contacts which responded to emails in the specified time range.
     *
     * @throws ClientException
     * @throws ServerException
     */
    public function getEmailResponses(array $data): Response;

    /**
     * Flags contacts as unsubscribed for an email campaign launch so they will be included in the campaign statistics.
     *
     * @throws ClientException
     * @throws ServerException
     */
    public function unsubscribeEmail(array $data): Response;

    /**
     * Returns a list of email categories which can be used in email creation.
     *
     * @throws ClientException
     * @throws ServerException
     */
    public function getEmailCategories(array $data): Response;

    /**
     * Returns a list of external events which can be used in program s .
     *
     * @throws ClientException
     * @throws ServerException
     */
    public function getEvents(): Response;

    /**
     * Triggers the given event for the specified contact.
     *
     * @throws ClientException
     * @throws ServerException
     */
    public function triggerEvent(string $eventId, array $data): Response;

    /**
     * Fetches the status data of an export.
     *
     * @throws ClientException
     * @throws ServerException
     */
    public function getExportStatus(array $data): Response;

    /**
     * Returns a list of fields (including custom fields and vouchers) which can be used to personalize content.
     *
     * @throws ClientException
     * @throws ServerException
     */
    public function getFields(): Response;

    /**
     * Returns the choice options of a field.
     *
     * @param string $fieldId Field ID or custom field name (available in fields mapping)
     *
     * @throws ClientException
     * @throws ServerException
     */
    public function getFieldChoices(string $fieldId): Response;

    /**
     * Returns a customer's files.
     *
     * @throws ClientException
     * @throws ServerException
     */
    public function getFiles(array $data): Response;

    /**
     * Uploads a file to a media database.
     *
     * @throws ClientException
     * @throws ServerException
     */
    public function uploadFile(array $data): Response;

    /**
     * Returns a list of segments which can be used as recipient source for the email.
     *
     * @throws ClientException
     * @throws ServerException
     */
    public function getSegments(array $data): Response;

    /**
     * Returns a customer's folders.
     *
     * @throws ClientException
     * @throws ServerException
     */
    public function getFolders(array $data): Response;

    /**
     * Returns a list of the customer's forms.
     *
     * @throws ClientException
     * @throws ServerException
     */
    public function getForms(array $data): Response;

    /**
     * Returns a list of languages which you can use in email creation.
     *
     * @throws ClientException
     * @throws ServerException
     */
    public function getLanguages(): Response;

    /**
     * Returns a list of sources which can be used for creating contacts.
     *
     * @throws ClientException
     * @throws ServerException
     */
    public function getSources(): Response;

    /**
     * Deletes an existing source.
     *
     * @throws ClientException
     * @throws ServerException
     */
    public function deleteSource(string $sourceId): Response;

    /**
     * Creates a new source for the customer with the specified name.
     *
     * @throws ClientException
     * @throws ServerException
     */
    public function createSource(array $data): Response;

    /**
     * Creates custom field in your Emarsys account
     *
     * @param string $applicationType shorttext|longtext|largetext|date|url|numeric
     *
     * @throws ClientException
     * @throws ServerException
     */
    public function createCustomField(string $name, string $applicationType): Response;

    /**
     * Adds a list of emails and domains to the blacklist
     *
     * @throws ClientException
     * @throws ServerException
     */
    public function addBlacklistEntries(array $emails = [], array $domains = []): Response;

    /**
     * Returns the current settings of the specified customer account, such as timezone or name.
     *
     * @throws ClientException
     * @throws ServerException
     */
    public function getSettings(): Response;
}
