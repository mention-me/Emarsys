<?php

namespace Snowcap\Emarsys;

use DateTime;
use Exception;
use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Client\ClientInterface as HttpClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Snowcap\Emarsys\ClientInterface as EmarsysClientInterface;
use Snowcap\Emarsys\Exception\ClientException;
use Snowcap\Emarsys\Exception\ServerException;

class Client implements EmarsysClientInterface
{
    public const HTTP_GET = 'GET';
    public const HTTP_POST = 'POST';
    public const HTTP_PUT = 'PUT';
    public const HTTP_DELETE = 'DELETE';
    public const LIVE_BASE_URL = 'https://api.emarsys.net/api/v2/';

    /**
     * @var string
     */
    private $baseUrl;

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
     * @param HttpClientInterface     $client         HTTP client implementation
     * @param RequestFactoryInterface $requestFactory HTTP request factory
     * @param StreamFactoryInterface  $streamFactory  PSR compliant stream factory
     * @param string                  $username       The username requested by the Emarsys API
     * @param string                  $secret         The secret requested by the Emarsys API
     * @param string|null             $baseUrl        Overrides the default baseUrl if needed
     * @param array                   $fieldsMapping  Overrides the default fields mapping if needed
     * @param array                   $choicesMapping Overrides the default choices mapping if needed
     */
    public function __construct(
        private readonly HttpClientInterface $client,
        private readonly RequestFactoryInterface $requestFactory,
        private readonly StreamFactoryInterface $streamFactory,
        private readonly string $username,
        private readonly string $secret,
        ?string $baseUrl = null,
        private array $fieldsMapping = [],
        private array $choicesMapping = []
    ) {
        $this->baseUrl = $baseUrl ?? $this::LIVE_BASE_URL;

        if (empty($this->fieldsMapping)) {
            $this->fieldsMapping = $this->parseFieldsJsonFile('fields.json');
        }

        if (empty($this->choicesMapping)) {
            $this->choicesMapping = $this->parseChoicesJsonFile('choices.json');
        }
    }

    /**
     * @param array $mapping
     */
    public function addFieldsMapping(array $mapping = []): void
    {
        $this->fieldsMapping = array_merge($this->fieldsMapping, $mapping);
    }

    /**
     * {@inheritDoc}
     */
    public function addChoicesMapping(array $mapping = []): void
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
     * {@inheritDoc}
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
     * {@inheritDoc}
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
     * {@inheritDoc}
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
     * {@inheritDoc}
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
     * {@inheritDoc}
     */
    public function getConditions(): Response
    {
        return $this->send('condition');
    }

    /**
     * {@inheritDoc}
     */
    public function createContact(array $data): Response
    {
        $data = $this->mapFieldsForMultipleContacts($data);

        return $this->send('contact', $this->mapFieldsToIds($data), self::HTTP_POST);
    }

    /**
     * {@inheritDoc}
     */
    public function updateContact(array $data): Response
    {
        $data = $this->mapFieldsForMultipleContacts($data);

        return $this->send('contact', $this->mapFieldsToIds($data), self::HTTP_PUT);
    }

    /**
     * {@inheritDoc}
     */
    public function updateContactAndCreateIfNotExists(array $data): Response
    {
        $data = $this->mapFieldsForMultipleContacts($data);

        return $this->send('contact/?create_if_not_exists=1', $this->mapFieldsToIds($data), self::HTTP_PUT);
    }

    /**
     * {@inheritDoc}
     */
    public function deleteContact(array $data): Response
    {
        return $this->send('contact/delete', $data, self::HTTP_POST);
    }

    /**
     * {@inheritDoc}
     */
    public function getContactId(string $fieldId, string $fieldValue): int
    {
        $response = $this->send(sprintf('contact/%s=%s', $fieldId, $fieldValue));

        $data = $response->getData();

        if (isset($data['id'])) {
            return $data['id'];
        }

        throw new ClientException($response->getReplyText(), $response->getReplyCode());
    }

    /**
     * {@inheritDoc}
     */
    public function getContactChanges(array $data): Response
    {
        return $this->send('contact/getchanges', $data, self::HTTP_POST);
    }

    /**
     * {@inheritDoc}
     */
    public function getContactHistory(array $data): Response
    {
        return $this->send('contact/getcontacthistory', $data, self::HTTP_POST);
    }

    /**
     * {@inheritDoc}
     */
    public function getContactData(array $data): Response
    {
        return $this->send('contact/getdata', $data, self::HTTP_POST);
    }

    /**
     * {@inheritDoc}
     */
    public function getContactRegistrations(array $data): Response
    {
        return $this->send('contact/getregistrations', $data, self::HTTP_POST);
    }

    /**
     * {@inheritDoc}
     */
    public function getContactList(array $data): Response
    {
        return $this->send('contactlist', $data);
    }

    /**
     * {@inheritDoc}
     */
    public function createContactList(array $data): Response
    {
        return $this->send('contactlist', $data, self::HTTP_POST);
    }

    /**
     * {@inheritDoc}
     */
    public function deleteContactList(string $listId): Response
    {
        return $this->send(sprintf('contactlist/%s/deletelist', $listId), [], self::HTTP_POST);
    }

    /**
     * {@inheritDoc}
     */
    public function addContactsToContactList(string $listId, array $data): Response
    {
        return $this->send(sprintf('contactlist/%s/add', $listId), $data, self::HTTP_POST);
    }

    /**
     * {@inheritDoc}
     */
    public function removeContactsFromContactList(string $listId, array $data): Response
    {
        return $this->send(sprintf('contactlist/%s/delete', $listId), $data, self::HTTP_POST);
    }

    /**
     * {@inheritDoc}
     */
    public function getContactsFromContactList(string $listId, array $data): Response
    {
        return $this->send(printf('contactlist/%s/contacts', $listId), $data);
    }

    /**
     * {@inheritDoc}
     */
    public function checkContactInList(int $contactId, int $listId): Response
    {
        return $this->send(printf('contactlist/%s/contacts/%s', $listId, $contactId));
    }

    /**
     * {@inheritDoc}
     */
    public function getEmails($status = null, $contactList = null, array $campaignTypes = []): Response
    {
        $data = [];
        if (null !== $status) {
            $data['status'] = $status;
        }
        if (null !== $contactList) {
            $data['contactlist'] = $contactList;
        }
        if ( ! empty($campaignTypes)) {
            $data['campaign_type'] = implode(',', $campaignTypes);
        }
        $url = 'email';
        if (count($data) > 0) {
            $url = sprintf('%s/%s', $url, http_build_query($data));
        }

        return $this->send($url);
    }

    /**
     * {@inheritDoc}
     */
    public function createEmail(array $data): Response
    {
        return $this->send('email', $data, self::HTTP_POST);
    }

    /**
     * {@inheritDoc}
     */
    public function getEmail(string $emailId, array $data): Response
    {
        return $this->send(printf('email/%s', $emailId), $data);
    }

    /**
     * {@inheritDoc}
     */
    public function launchEmail(string $emailId, array $data): Response
    {
        return $this->send(sprintf('email/%s/launch', $emailId), $data, self::HTTP_POST);
    }

    /**
     * {@inheritDoc}
     */
    public function previewEmail(string $emailId, array $data): Response
    {
        return $this->send(sprintf('email/%s/launch', $emailId), $data, self::HTTP_POST);
    }

    /**
     * {@inheritDoc}
     */
    public function getEmailResponseSummary(
        string $emailId,
        ?string $startDate = null,
        ?string $endDate = null,
        string $launchId = null
    ): Response {
        $data = [];

        if (null !== $startDate) {
            $data['start_date'] = $startDate;
        }

        if (null !== $endDate) {
            $data['end_date'] = $endDate;
        }

        if (null !== $launchId) {
            $data['launch_id'] = $launchId;
        }

        $url = sprintf('email/%s/responsesummary', $emailId);

        if (count($data) > 0) {
            $url = sprintf('%s/%s', $url, http_build_query($data));
        }

        return $this->send($url);
    }

    /**
     * {@inheritDoc}
     */
    public function sendEmailTest(string $emailId, array $data): Response
    {
        return $this->send(sprintf('email/%s/sendtestmail', $emailId), $data, self::HTTP_POST);
    }

    /**
     * {@inheritDoc}
     */
    public function getEmailUrl(string $emailId, array $data): Response
    {
        return $this->send(sprintf('email/%s/url', $emailId), $data, self::HTTP_POST);
    }

    /**
     * {@inheritDoc}
     */
    public function getEmailDeliveryStatus(array $data): Response
    {
        return $this->send('email/getdeliverystatus', $data, self::HTTP_POST);
    }

    /**
     * {@inheritDoc}
     */
    public function getEmailLaunches(array $data): Response
    {
        return $this->send('email/getlaunchesofemail', $data, self::HTTP_POST);
    }

    /**
     * {@inheritDoc}
     */
    public function getEmailResponses(array $data): Response
    {
        return $this->send('email/getresponses', $data, self::HTTP_POST);
    }

    /**
     * {@inheritDoc}
     */
    public function unsubscribeEmail(array $data): Response
    {
        return $this->send('email/unsubscribe', $data, self::HTTP_POST);
    }

    /**
     * {@inheritDoc}
     */
    public function getEmailCategories(array $data): Response
    {
        return $this->send('emailcategory', $data);
    }

    /**
     * {@inheritDoc}
     */
    public function getEvents(): Response
    {
        return $this->send('event');
    }

    /**
     * {@inheritDoc}
     */
    public function triggerEvent(string $eventId, array $data): Response
    {
        return $this->send(sprintf('event/%s/trigger', $eventId), $data, self::HTTP_POST);
    }

    /**
     * {@inheritDoc}
     */
    public function getExportStatus(array $data): Response
    {
        return $this->send('export', $data);
    }

    /**
     * {@inheritDoc}
     */
    public function getFields(): Response
    {
        return $this->send('field');
    }

    /**
     * {@inheritDoc}
     */
    public function getFieldChoices(string $fieldId): Response
    {
        return $this->send(printf('field/%s/choice', $this->getFieldId($fieldId)));
    }

    /**
     * {@inheritDoc}
     */
    public function getFiles(array $data): Response
    {
        return $this->send('file', $data);
    }

    /**
     * {@inheritDoc}
     */
    public function uploadFile(array $data): Response
    {
        return $this->send('file', $data, self::HTTP_POST);
    }

    /**
     * {@inheritDoc}
     */
    public function getSegments(array $data): Response
    {
        return $this->send('filter', $data);
    }

    /**
     * {@inheritDoc}
     */
    public function getFolders(array $data): Response
    {
        return $this->send('folder', $data);
    }

    /**
     * {@inheritDoc}
     */
    public function getForms(array $data): Response
    {
        return $this->send('form', $data);
    }

    /**
     * {@inheritDoc}
     */
    public function getLanguages(): Response
    {
        return $this->send('language');
    }

    /**
     * {@inheritDoc}
     */
    public function getSources(): Response
    {
        return $this->send('source');
    }

    /**
     * {@inheritDoc}
     */
    public function deleteSource(string $sourceId): Response
    {
        return $this->send(sprintf('source/%s/delete', $sourceId), [], self::HTTP_DELETE);
    }

    /**
     * {@inheritDoc}
     */
    public function createSource(array $data): Response
    {
        return $this->send('source/create', $data, self::HTTP_POST);
    }

    /**
     * {@inheritDoc}
     */
    public function createCustomField(string $name, string $applicationType): Response
    {
        return $this->send(
            'field',
            [
                'name'             => $name,
                'application_type' => $applicationType,
            ],
            self::HTTP_POST
        );
    }

    /**
     * {@inheritDoc}
     */
    public function addBlacklistEntries(array $emails = [], array $domains = []): Response
    {
        return $this->send(
            'blacklist',
            [
                'emails'  => $emails,
                'domains' => $domains,
            ],
            self::HTTP_POST
        );
    }

    /**
     * {@inheritDoc}
     */
    public function getSettings(): Response
    {
        return $this->send('settings');
    }

    /**
     * Send an HTTP request
     *
     *
     * @return Response
     * @throws ClientException
     * @throws ServerException
     * @throws Exception
     */
    protected function send(string $uri, array $body = [], string $method = self::HTTP_GET): Response
    {
        $uri = $this->baseUrl . $uri;

        $request = $this->requestFactory->createRequest($method, $uri);

        $request = $request->withHeader('Content-Type', 'application/json');
        $request = $request->withHeader('X-WSSE', $this->getAuthenticationSignature());
        $request = $request->withBody($this->streamFactory->createStream(json_encode($body)));

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
        // the current time formatted Y-m-d\TH:i:sP
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
     * @return mixed
     */
    private function readJsonFile(string $filename)
    {
        $json = file_get_contents(__DIR__ . '/json/' . $filename);

        return json_decode($json, true);
    }

    private function parseFieldsJsonFile(string $filename): array
    {
        $jsonObject = $this->readJsonFile($filename);

        return $this->castJsonObjectFileToFields($jsonObject);
    }

    private function parseChoicesJsonFile(string $filename): array
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
     *
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
     *
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
                    $this->mapFieldsToIds(...),
                    $data['contacts']
                ),
            ]
        );
    }
}
