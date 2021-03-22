<?php

namespace Snowcap\Emarsys;

use DateTime;
use Exception;
use Http\Message\RequestFactory;
use Psr\Http\Client\ClientExceptionInterface;
use Snowcap\Emarsys\Exception\ClientException;
use Snowcap\Emarsys\Exception\ServerException;
use Psr\Http\Client\ClientInterface;

class Client
{
    public const GET = 'GET';
    public const POST = 'POST';
    public const PUT = 'PUT';
    public const DELETE = 'DELETE';
    public const PATCH = 'PATCH';

    public const EMAIL_STATUS_IN_DESIGN = 1;
    public const EMAIL_STATUS_TESTED = 2;
    public const EMAIL_STATUS_LAUNCHED = 3;
    public const EMAIL_STATUS_READY = 4;
    public const EMAIL_STATUS_DEACTIVATED = -3;

    public const LAUNCH_STATUS_NOT_LAUNCHED = 0;
    public const LAUNCH_STATUS_IN_PROGRESS = 1;
    public const LAUNCH_STATUS_SCHEDULED = 2;
    public const LAUNCH_STATUS_ERROR = -10;

    public const LIVE_BASE_URL = 'https://api.emarsys.net/api/v2/';

    /**
     * @var string
     */
    private $baseUrl = 'https://api.emarsys.net/api/v2/';

    /**
     * @var string
     */
    private $username;

    /**
     * @var string
     */
    private $secret;

    /**
     * @var ClientInterface
     */
    private $client;

    /**
     * @var RequestFactory
     */
    private $requestFactory;

    /**
     * @var array
     */
    private $fieldsMapping;

    /**
     * @var array
     */
    private $choicesMapping;

    /**
     * @var array
     */
    private $systemFields = [
        'key_id',
        'id',
        'contacts',
        'uid',
    ];

    /**
     * @param ClientInterface $client         HTTP client implementation
     * @param RequestFactory  $requestFactory HTTP request factory
     * @param string          $username       The username requested by the Emarsys API
     * @param string          $secret         The secret requested by the Emarsys API
     * @param string|null     $baseUrl        Overrides the default baseUrl if needed
     * @param array           $fieldsMapping  Overrides the default fields mapping if needed
     * @param array           $choicesMapping Overrides the default choices mapping if needed
     */
    public function __construct(
        ClientInterface $client,
        RequestFactory $requestFactory,
        string $username,
        string $secret,
        $baseUrl = null,
        $fieldsMapping = [],
        $choicesMapping = []
    ) {
        $this->client = $client;
        $this->requestFactory = $requestFactory;
        $this->username = $username;
        $this->secret = $secret;
        $this->fieldsMapping = $fieldsMapping;
        $this->choicesMapping = $choicesMapping;

        $this->baseUrl = $baseUrl ?? $this::LIVE_BASE_URL;

        if (empty($this->fieldsMapping)) {
            $this->fieldsMapping = $this->parseFieldsIniFile('fields.json');
        }

        if (empty($this->choicesMapping)) {
            $this->choicesMapping = $this->parseChoicesIniFile('choices.json');
        }
    }

    /**
     * Add your custom fields mapping
     * This is useful if you want to use string identifiers instead of ids when you play with contacts fields
     *
     * Example:
     *  $mapping = [
     *      'myCustomField' => 7147,
     *      'myCustomField2' => 7148,
     *  ];
     *
     * @param array $mapping
     */
    public function addFieldsMapping($mapping = []): void
    {
        $this->fieldsMapping = array_merge($this->fieldsMapping, $mapping);
    }

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
     *
     * @param array $mapping
     */
    public function addChoicesMapping($mapping = []): void
    {
        foreach ($mapping as $fieldStringId => $choices) {
            if (is_array($choices)) {
                if ( ! array_key_exists($fieldStringId, $this->choicesMapping)) {
                    $this->choicesMapping[$fieldStringId] = [];
                }

                $this->choicesMapping[$fieldStringId] = array_merge($this->choicesMapping[$fieldStringId], $choices);
            }
        }
    }

    /**
     * Returns a field id from a field string_id (specified in the fields mapping)
     *
     * @param string $fieldStringId
     *
     * @return int
     * @throws ClientException
     */
    public function getFieldId(string $fieldStringId): int
    {
        if (in_array($fieldStringId, $this->systemFields)) {
            return $fieldStringId;
        }

        if ( ! isset($this->fieldsMapping[$fieldStringId])) {
            throw ClientException::unrecognizedFieldName($fieldStringId);
        }

        return (int) $this->fieldsMapping[$fieldStringId];
    }

    /**
     * Returns a field name from a field id (specified in the fields mapping) or the field id if no mapping is found
     *
     * @param string|int $fieldId
     *
     * @return string|int
     */
    public function getFieldStringId($fieldId)
    {
        $fieldName = array_search($fieldId, $this->fieldsMapping);

        if ($fieldName) {
            return $fieldName;
        }

        return $fieldId;
    }

    /**
     * Returns a choice id for a field from a choice name (specified in the choices mapping)
     *
     * @param string|int $fieldStringId
     * @param string|int $choice
     *
     * @return int
     * @throws ClientException
     */
    public function getChoiceId($fieldStringId, $choice): int
    {
        if ( ! array_key_exists($fieldStringId, $this->choicesMapping)) {
            throw ClientException::unrecognizedFieldStringIdForChoice($fieldStringId, $choice);
        }

        if ( ! isset($this->choicesMapping[$fieldStringId][$choice])) {
            throw ClientException::unrecognizedChoiceForFieldStringId($choice, $fieldStringId);
        }

        return (int) $this->choicesMapping[$fieldStringId][$choice];
    }

    /**
     * Returns a choice name for a field from a choice id (specified in the choices mapping) or the choice id if no
     * mapping is found
     *
     * @param string|int $fieldId
     * @param int        $choiceId
     *
     * @return string|int
     * @throws ClientException
     */
    public function getChoiceName($fieldId, int $choiceId)
    {
        $fieldStringId = $fieldId;
        if (is_int($fieldId)) {
            $fieldStringId = $this->getFieldStringId($fieldId);
        }

        if ( ! array_key_exists($fieldStringId, $this->choicesMapping)) {
            throw ClientException::unrecognizedFieldStringIdForChoice($fieldId, $choiceId);
        }
        $choiceName = null;
        foreach ($this->choicesMapping[$fieldId] as $key => $choiceValue) {
            // The id in the choicesMapping is a string so we only use == for comparison
            if ($choiceId == $choiceValue) {
                $choiceName = $key;
                break;
            }
        }

        if ($choiceName) {
            return $choiceName;
        }

        return $choiceId;
    }

    /**
     * Returns a list of condition rules.
     *
     * @return Response
     * @throws ClientException
     * @throws ServerException
     */
    public function getConditions(): Response
    {
        return $this->send($this::GET, 'condition');
    }

    /**
     * Creates one or more new contacts/recipients.
     * Example :
     *  $data = array(
     *      'key_id' => '3',
     *      '3' => 'recipient@example.com',
     *      'source_id' => '123',
     *  );
     *
     * @param array $data
     *
     * @return Response
     * @throws ClientException
     * @throws ServerException
     */
    public function createContact(array $data): Response
    {
        $data = $this->mapFieldsForMultipleContacts($data);

        return $this->send($this::POST, 'contact', $this->mapFieldsToIds($data));
    }

    /**
     * Updates one or more contacts/recipients, identified by an external ID.
     *
     * @param array $data
     *
     * @return Response
     * @throws ClientException
     * @throws ServerException
     */
    public function updateContact(array $data): Response
    {
        $data = $this->mapFieldsForMultipleContacts($data);

        return $this->send($this::PUT, 'contact', $this->mapFieldsToIds($data));
    }

    /**
     * Updates one or more contacts/recipients, identified by an external ID. If the contact does not exist in the
     * database, it is created.
     *
     * @param array $data
     *
     * @return Response
     * @throws ClientException
     * @throws ServerException
     */
    public function updateContactAndCreateIfNotExists(array $data): Response
    {
        $data = $this->mapFieldsForMultipleContacts($data);

        return $this->send($this::PUT, 'contact/?create_if_not_exists=1', $this->mapFieldsToIds($data));
    }

    /**
     * Deletes a single contact/recipient, identified by an external ID.
     *
     * @param array $data
     *
     * @return Response
     * @throws ClientException
     * @throws ServerException
     */
    public function deleteContact(array $data): Response
    {
        return $this->send($this::POST, 'contact/delete', $data);
    }

    /**
     * Returns the internal ID of a contact specified by its external ID.
     *
     * @param string $fieldId
     * @param string $fieldValue
     *
     * @return int
     * @throws ClientException
     * @throws ServerException
     */
    public function getContactId(string $fieldId, string $fieldValue): int
    {
        $response = $this->send($this::GET, sprintf('contact/%s=%s', $fieldId, $fieldValue));

        $data = $response->getData();

        if (isset($data['id'])) {
            return $data['id'];
        }

        throw new ClientException($response->getReplyText(), $response->getReplyCode());
    }

    /**
     * Exports the selected fields of all contacts with properties changed in the time range specified.
     *
     * @param array $data
     *
     * @return Response
     * @throws ClientException
     * @throws ServerException
     */
    public function getContactChanges(array $data): Response
    {
        return $this->send($this::POST, 'contact/getchanges', $data);
    }

    /**
     * Returns the list of emails sent to the specified contacts.
     *
     * @param array $data
     *
     * @return Response
     * @throws ClientException
     * @throws ServerException
     */
    public function getContactHistory(array $data): Response
    {
        return $this->send($this::POST, 'contact/getcontacthistory', $data);
    }

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
     * @param array $data
     *
     * @return Response
     * @throws ClientException
     * @throws ServerException
     */
    public function getContactData(array $data): Response
    {
        return $this->send($this::POST, 'contact/getdata', $data);
    }

    /**
     * Exports the selected fields of all contacts which registered in the specified time range.
     *
     * @param array $data
     *
     * @return Response
     * @throws ClientException
     * @throws ServerException
     */
    public function getContactRegistrations(array $data): Response
    {
        return $this->send($this::POST, 'contact/getregistrations', $data);
    }

    /**
     * Returns a list of contact lists which can be used as recipient source for the email.
     *
     * @param array $data
     *
     * @return Response
     * @throws ClientException
     * @throws ServerException
     */
    public function getContactList(array $data): Response
    {
        return $this->send($this::GET, 'contactlist', $data);
    }

    /**
     * Creates a contact list which can be used as recipient source for the email.
     *
     * @param array $data
     *
     * @return Response
     * @throws ClientException
     * @throws ServerException
     */
    public function createContactList(array $data): Response
    {
        return $this->send($this::POST, 'contactlist', $data);
    }

    /**
     * Deletes a contact list which can be used as recipient source for the email.
     *
     * @param string $listId
     *
     * @return Response
     * @throws ClientException
     * @throws ServerException
     */
    public function deleteContactList(string $listId): Response
    {
        return $this->send($this::POST, sprintf('contactlist/%s/deletelist', $listId));
    }

    /**
     * Creates a contact list which can be used as recipient source for the email.
     *
     * @param string $listId
     * @param array  $data
     *
     * @return Response
     * @throws ClientException
     * @throws ServerException
     */
    public function addContactsToContactList(string $listId, array $data): Response
    {
        return $this->send($this::POST, sprintf('contactlist/%s/add', $listId), $data);
    }

    /**
     * This deletes contacts from the contact list which can be used as recipient source for the email.
     *
     * @param string $listId
     * @param array  $data
     *
     * @return Response
     * @throws ClientException
     * @throws ServerException
     */
    public function removeContactsFromContactList(string $listId, array $data): Response
    {
        return $this->send($this::POST, sprintf('contactlist/%s/delete', $listId), $data);
    }

    /**
     * Get a list of contact IDs that are in a contact list
     *
     * @param string $listId
     * @param array  $data
     *
     * @return Response
     * @throws ClientException
     * @throws ServerException
     */
    public function getContactsFromContactList(string $listId, array $data): Response
    {
        return $this->send($this::GET, sprintf('contactlist/%s/contacts', $listId), $data);
    }

    /**
     * Checks whether a specific contact is included in the defined contact list.
     *
     * @param int $contactId
     * @param int $listId
     *
     * @return Response
     * @throws ClientException
     * @throws ServerException
     * @link http://documentation.emarsys.com/resource/developers/endpoints/contacts/check-a-contact-in-a-contact-list/
     */
    public function checkContactInList(int $contactId, int $listId): Response
    {
        return $this->send($this::GET, sprintf('contactlist/%s/contacts/%s', $listId, $contactId));
    }

    /**
     * Returns a list of emails.
     *
     * @param int|null $status
     * @param int|null $contactList
     *
     * @return Response
     * @throws ClientException
     * @throws ServerException
     */
    public function getEmails($status = null, $contactList = null): Response
    {
        $data = [];
        if (null !== $status) {
            $data['status'] = $status;
        }
        if (null !== $contactList) {
            $data['contactlist'] = $contactList;
        }
        $url = 'email';
        if (count($data) > 0) {
            $url = sprintf('%s/%s', $url, http_build_query($data));
        }

        return $this->send($this::GET, $url);
    }

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
     * @param array $data
     *
     * @return Response
     * @throws ClientException
     * @throws ServerException
     */
    public function createEmail(array $data): Response
    {
        return $this->send($this::POST, 'email', $data);
    }

    /**
     * Returns the attributes of an email and the personalized text and HTML source.
     *
     * @param string $emailId
     * @param array  $data
     *
     * @return Response
     * @throws ClientException
     * @throws ServerException
     */
    public function getEmail(string $emailId, array $data): Response
    {
        return $this->send($this::GET, sprintf('email/%s', $emailId), $data);
    }

    /**
     * Launches an email. This is an asynchronous call, which returns 'OK' if the email is able to launch.
     *
     * @param string $emailId
     * @param array  $data
     *
     * @return Response
     * @throws ClientException
     * @throws ServerException
     */
    public function launchEmail(string $emailId, array $data): Response
    {
        return $this->send($this::POST, sprintf('email/%s/launch', $emailId), $data);
    }

    /**
     * Returns the HTML or text version of the email either as content type 'application/json' or 'text/html'.
     *
     * @param string $emailId
     * @param array  $data
     *
     * @return Response
     * @throws ClientException
     * @throws ServerException
     */
    public function previewEmail(string $emailId, array $data): Response
    {
        return $this->send($this::POST, sprintf('email/%s/launch', $emailId), $data);
    }

    /**
     * Returns the summary of the responses of a launched, paused, activated or deactivated email.
     *
     * @param string $emailId
     * @param array  $data
     *
     * @return Response
     * @throws ClientException
     * @throws ServerException
     */
    public function getEmailResponseSummary(string $emailId, array $data): Response
    {
        return $this->send($this::POST, sprintf('email/%s/responsesummary', $emailId), $data);
    }

    /**
     * Instructs the system to send a test email.
     *
     * @param string $emailId
     * @param array  $data
     *
     * @return Response
     * @throws ClientException
     * @throws ServerException
     */
    public function sendEmailTest(string $emailId, array $data): Response
    {
        return $this->send($this::POST, sprintf('email/%s/sendtestmail', $emailId), $data);
    }

    /**
     * Returns the URL to the online version of an email, provided it has been sent to the specified contact.
     *
     * @param string $emailId
     * @param array  $data
     *
     * @return Response
     * @throws ClientException
     * @throws ServerException
     */
    public function getEmailUrl(string $emailId, array $data): Response
    {
        return $this->send($this::POST, sprintf('email/%s/url', $emailId), $data);
    }

    /**
     * Returns the delivery status of an email.
     *
     * @param array $data
     *
     * @return Response
     * @throws ClientException
     * @throws ServerException
     */
    public function getEmailDeliveryStatus(array $data): Response
    {
        return $this->send($this::POST, 'email/getdeliverystatus', $data);
    }

    /**
     * Lists all the launches of an email with ID, launch date and 'done' status.
     *
     * @param array $data
     *
     * @return Response
     * @throws ClientException
     * @throws ServerException
     */
    public function getEmailLaunches(array $data): Response
    {
        return $this->send($this::POST, 'email/getlaunchesofemail', $data);
    }

    /**
     * Exports the selected fields of all contacts which responded to emails in the specified time range.
     *
     * @param array $data
     *
     * @return Response
     * @throws ClientException
     * @throws ServerException
     */
    public function getEmailResponses(array $data): Response
    {
        return $this->send($this::POST, 'email/getresponses', $data);
    }

    /**
     * Flags contacts as unsubscribed for an email campaign launch so they will be included in the campaign statistics.
     *
     * @param array $data
     *
     * @return Response
     * @throws ClientException
     * @throws ServerException
     */
    public function unsubscribeEmail(array $data): Response
    {
        return $this->send($this::POST, 'email/unsubscribe', $data);
    }

    /**
     * Returns a list of email categories which can be used in email creation.
     *
     * @param array $data
     *
     * @return Response
     * @throws ClientException
     * @throws ServerException
     */
    public function getEmailCategories(array $data): Response
    {
        return $this->send($this::GET, 'emailcategory', $data);
    }

    /**
     * Returns a list of external events which can be used in program s .
     *
     * @return Response
     * @throws ClientException
     * @throws ServerException
     */
    public function getEvents(): Response
    {
        return $this->send($this::GET, 'event');
    }

    /**
     * Triggers the given event for the specified contact.
     *
     * @param string $eventId
     * @param array  $data
     *
     * @return Response
     * @throws ClientException
     * @throws ServerException
     */
    public function triggerEvent(string $eventId, array $data): Response
    {
        return $this->send($this::POST, sprintf('event/%s/trigger', $eventId), $data);
    }

    /**
     * Fetches the status data of an export.
     *
     * @param array $data
     *
     * @return Response
     * @throws ClientException
     * @throws ServerException
     */
    public function getExportStatus(array $data): Response
    {
        return $this->send($this::GET, 'export', $data);
    }

    /**
     * Returns a list of fields (including custom fields and vouchers) which can be used to personalize content.
     *
     * @return Response
     * @throws ClientException
     * @throws ServerException
     */
    public function getFields(): Response
    {
        return $this->send($this::GET, 'field');
    }

    /**
     * Returns the choice options of a field.
     *
     * @param string $fieldId Field ID or custom field name (available in fields mapping)
     *
     * @return Response
     * @throws ClientException
     * @throws ServerException
     */
    public function getFieldChoices(string $fieldId): Response
    {
        return $this->send($this::GET, sprintf('field/%s/choice', $this->getFieldId($fieldId)));
    }

    /**
     * Returns a customer's files.
     *
     * @param array $data
     *
     * @return Response
     * @throws ClientException
     * @throws ServerException
     */
    public function getFiles(array $data): Response
    {
        return $this->send($this::GET, 'file', $data);
    }

    /**
     * Uploads a file to a media database.
     *
     * @param array $data
     *
     * @return Response
     * @throws ClientException
     * @throws ServerException
     */
    public function uploadFile(array $data): Response
    {
        return $this->send($this::POST, 'file', $data);
    }

    /**
     * Returns a list of segments which can be used as recipient source for the email.
     *
     * @param array $data
     *
     * @return Response
     * @throws ClientException
     * @throws ServerException
     */
    public function getSegments(array $data): Response
    {
        return $this->send($this::GET, 'filter', $data);
    }

    /**
     * Returns a customer's folders.
     *
     * @param array $data
     *
     * @return Response
     * @throws ClientException
     * @throws ServerException
     */
    public function getFolders(array $data): Response
    {
        return $this->send($this::GET, 'folder', $data);
    }

    /**
     * Returns a list of the customer's forms.
     *
     * @param array $data
     *
     * @return Response
     * @throws ClientException
     * @throws ServerException
     */
    public function getForms(array $data): Response
    {
        return $this->send($this::GET, 'form', $data);
    }

    /**
     * Returns a list of languages which you can use in email creation.
     *
     * @return Response
     * @throws ClientException
     * @throws ServerException
     */
    public function getLanguages(): Response
    {
        return $this->send($this::GET, 'language');
    }

    /**
     * Returns a list of sources which can be used for creating contacts.
     *
     * @return Response
     * @throws ClientException
     * @throws ServerException
     */
    public function getSources(): Response
    {
        return $this->send($this::GET, 'source');
    }

    /**
     * Deletes an existing source.
     *
     * @param string $sourceId
     *
     * @return Response
     * @throws ClientException
     * @throws ServerException
     */
    public function deleteSource(string $sourceId): Response
    {
        return $this->send($this::DELETE, sprintf('source/%s/delete', $sourceId));
    }

    /**
     * Creates a new source for the customer with the specified name.
     *
     * @param array $data
     *
     * @return Response
     * @throws ClientException
     * @throws ServerException
     */
    public function createSource(array $data): Response
    {
        return $this->send($this::POST, 'source/create', $data);
    }

    /**
     * Creates custom field in your Emarsys account
     *
     * @param string $name
     * @param string $applicationType shorttext|longtext|largetext|date|url|numeric
     *
     * @return Response
     * @throws ClientException
     * @throws ServerException
     */
    public function createCustomField(string $name, string $applicationType): Response
    {
        return $this->send(
            $this::POST,
            'field',
            [
                'name'             => $name,
                'application_type' => $applicationType,
            ]
        );
    }

    /**
     * Adds a list of emails and domains to the blacklist
     *
     * @param array $emails
     * @param array $domains
     *
     * @return Response
     * @throws ClientException
     * @throws ServerException
     */
    public function addBlacklistEntries(array $emails = [], array $domains = []): Response
    {
        return $this->send(
            $this::POST,
            'blacklist',
            [
                'emails'  => $emails,
                'domains' => $domains,
            ]
        );
    }

    /**
     * Send an HTTP request
     *
     * @param string $method
     * @param string $uri
     * @param array  $body
     *
     * @return Response
     * @throws ClientException
     * @throws ServerException
     */
    protected function send($method = 'GET', $uri, array $body = []): Response
    {
        $headers = [
            'Content-Type' => 'application/json',
            'X-WSSE'       => $this->getAuthenticationSignature(),
        ];

        $uri = $this->baseUrl . $uri;

        $request = $this->requestFactory->createRequest($method, $uri, $headers, json_encode($body));

        try {
            $response = $this->client->sendRequest($request);
        } catch (Exception $e) {
            throw new ServerException($e->getMessage());
        } catch (ClientExceptionInterface $e) {
            throw new ClientException($e->getMessage());
        }

        $responseArray = json_decode($response->getBody(), true);

        if ($responseArray === null) {
            switch (json_last_error()) {
                case JSON_ERROR_DEPTH:
                    throw ClientException::jsonMaximumDepthDecodingException();
                default:
                    throw ServerException::jsonDecodingException(json_last_error_msg());
            }
        }
        if (is_array($responseArray) === false) {
            throw ServerException::jsonResponseNotArrayException($responseArray);
        }

        return new Response($responseArray);
    }

    /**
     * Generate X-WSSE signature used to authenticate
     *
     * @return string
     * @throws Exception
     */
    protected function getAuthenticationSignature(): string
    {
        // the current time encoded as an ISO 8601 date string
        $created = new DateTime();
        $iso8601 = $created->format(DateTime::ATOM);
        // the md5 of a random string . e.g. a timestamp
        $nonce = md5($created->modify('next friday')->getTimestamp());
        // The algorithm to generate the digest is as follows:
        // Concatenate: Nonce + Created + Secret
        // Hash the result using the SHA1 algorithm
        // Encode the result to base64
        $digest = base64_encode(sha1($nonce . $iso8601 . $this->secret));

        $signature = sprintf(
            'UsernameToken Username="%s", PasswordDigest="%s", Nonce="%s", Created="%s"',
            $this->username,
            $digest,
            $nonce,
            $iso8601
        );

        return $signature;
    }

    /**
     * Convert field string ids to field ids
     *
     * @param array $data
     *
     * @return array
     * @throws ClientException
     */
    private function mapFieldsToIds(array $data): array
    {
        $mappedData = [];

        foreach ($data as $fieldStringId => $value) {
            if (is_numeric($fieldStringId)) {
                $mappedData[(int) $fieldStringId] = $value;
            } else {
                $mappedData[$this->getFieldId($fieldStringId)] = $value;
            }
        }

        return $mappedData;
    }

    /**
     * @param $filename
     *
     * @return mixed
     */
    private function readJsonFile($filename)
    {
        $json = file_get_contents(__DIR__ . '/json/' . $filename);

        return json_decode($json, true);
    }

    private function parseFieldsIniFile($filename): array
    {
        $jsonObject = $this->readJsonFile($filename);

        return $this->castJsonObjectFileToFields($jsonObject);
    }

    private function parseChoicesIniFile($filename): array
    {
        $jsonObject = $this->readJsonFile($filename);

        return $this->castJsonObjectToChoices($jsonObject);
    }

    /**
     * This will take a JSON object with the following structure (same as the data property of the result when making a
     * https://dev.emarsys.com/v2/fields/list-available-fields request)
     *
     * [
     *   {
     *   "id": 0,
     *   "name": "Interests",
     *   "application_type": "interests",
     *   "string_id": "interests"
     *   }
     * ]
     *
     * and cast it into the fields mapping structure
     *
     *  $fieldsMapping = [
     *      'myCustomField' => 7147,
     *      'myCustomField2' => 7148,
     *  ];
     *
     * @param $data
     * @return array
     */
    private function castJsonObjectFileToFields($data): array
    {
        $mapping = [];
        foreach ($data as $field) {
            $mapping[$field['string_id']] = $field['id'];
        }

        return $mapping;
    }

    /**
     * This will take a JSON object with the following structure (which can easily be put together from responses from
     * the https://dev.emarsys.com/v2/fields/list-available-choices-of-a-single-field endpoint)
     *
     * {
     *  "9": [
     *    {
     *      "id": "4",
     *      "choice": "Dr."
     *    },
     *    {
     *      "id": "5",
     *      "choice": "Mag."
     *    }
     *  ]
     * }
     *
     * and cast it into the choices mapping structure
     *
     * $choicesMapping = [
     *      'fieldId' => [
     *          'choiceName1' => 1,
     *          'choiceName2' => 2,
     *      ]
     *  ];
     *
     * @param $data
     * @return array
     */
    private function castJsonObjectToChoices($data): array
    {
        $mapping = [];
        foreach ($data as $key => $value) {
            $fieldStringId = $this->getFieldStringId($key);
            $mapping[$fieldStringId] = [];
            foreach ($value as $choice) {
                $mapping[$fieldStringId] = [$choice['choice'] => $choice['id']];
            }
        }

        return $mapping;
    }

    /**
     * @param array $data
     *
     * @return array
     */
    private function mapFieldsForMultipleContacts(array $data): array
    {
        if ( ! isset($data['contacts']) || ! is_array($data['contacts'])) {
            return $data;
        }

        return array_merge(
            $data,
            [
                'contacts' => array_map(
                    [
                        $this,
                        'mapFieldsToIds',
                    ],
                    $data['contacts']
                ),
            ]
        );
    }
}
