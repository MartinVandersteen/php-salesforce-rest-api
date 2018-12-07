<?php

namespace bjsmasth\Salesforce;

use GuzzleHttp\Client;
use Exception\Salesforce as SalesforceException;

class CRUD
{
    protected $instance_url;
    protected $access_token;
    protected $refresh_token;

    public function __construct($instance_url = null, $access_token = null, $refresh_token = null, $client_id = null, $client_secret = null)
    {
        if (empty($instance_url) && empty($access_token) && !isset($_SESSION) and !isset($_SESSION['salesforce'])) {
            throw new SalesforceException('Access Denied', 403);
        }

        $this->instance_url = $instance_url ?? $_SESSION['salesforce']['instance_url'];
        $this->access_token = $access_token ?? $_SESSION['salesforce']['access_token'];
        
        if($refresh_token && $this->instance_url && $client_id && $client_secret) {
            $url = $this->instance_url.'/services/oauth2/token';
             $client = new Client();
             $request = $client->request('POST', $url, [
                'query' => [
                    'grant_type' => 'refresh_token',
                    'client_id'  => $client_id,
                    'client_secret' => $client_secret,
                    'refresh_token' => $refresh_token
                ]
            ]);
            
            if($request->getStatusCode(200)) {
                $this->access_token = json_decode($request->getBody(), true)['access_token'];
                $this->instance_url = json_decode($request->getBody(), true)['instance_url'];
            }
        }
    }

    public function query($query)
    {
        $url = "{$this->instance_url}/services/data/v39.0/query";

        $client = new Client();
        $request = $client->request('GET', $url, [
            'headers' => [
                'Authorization' => "OAuth {$this->access_token}"
            ],
            'query' => [
                'q' => $query
            ]
        ]);

        return json_decode($request->getBody(), true);
    }

    public function create($object, array $data)
    {
        $url = "{$this->instance_url}/services/data/v39.0/sobjects/{$object}/";

        $client = new Client();

        $request = $client->request('POST', $url, [
            'headers' => [
                'Authorization' => "OAuth {$this->access_token}",
                'Content-type' => 'application/json'
            ],
            'json' => $data
        ]);

        $status = $request->getStatusCode();

        if ($status != 201) {
            throw new SalesforceException(
                "Error: call to URL {$url} failed with status {$status}, response: {$request->getReasonPhrase()}"
            );
        }

        $response = json_decode($request->getBody(), true);
        $id = $response["id"];

        return $id;

    }

    public function update($object, $id, array $data)
    {
        $url = "{$this->instance_url}/services/data/v39.0/sobjects/{$object}/{$id}";

        $client = new Client();

        $request = $client->request('PATCH', $url, [
            'headers' => [
                'Authorization' => "OAuth $this->access_token",
                'Content-type' => 'application/json'
            ],
            'json' => $data
        ]);

        $status = $request->getStatusCode();

        if ($status != 204) {
            throw new SalesforceException(
                "Error: call to URL {$url} failed with status {$status}, response: {$request->getReasonPhrase()}"
            );
        }

        return $status;
    }
    
    public function massUpdate(array $data)
    {
        $url = "{$this->instance_url}/services/data/v39.0/composite/sobjects";

        $client = new Client();

        $request = $client->request('PATCH', $url, [
            'headers' => [
                'Authorization' => "OAuth $this->access_token",
                'Content-type' => 'application/json'
            ],
            'json' => $data
        ]);

        $status = $request->getStatusCode();

        if ($status != 204) {
            throw new SalesforceException(
                "Error: call to URL {$url} failed with status {$status}, response: {$request->getReasonPhrase()}"
            );
        }

        return $status;
    }

    public function upsert($object, $field, $id, array $data)
    {
        $url = "{$this->instance_url}/services/data/v39.0/sobjects/{$object}/{$field}/{$id}";

        $client = new Client();

        $request = $client->request('PATCH', $url, [
            'headers' => [
                'Authorization' => "OAuth {$this->access_token}",
                'Content-type' => 'application/json'
            ],
            'json' => $data
        ]);

        $status = $request->getStatusCode();

        if ($status != 204 && $status != 201) {
            throw new SalesforceException(
                "Error: call to URL {$url} failed with status {$status}, response: {$request->getReasonPhrase()}"
            );
        }

        return $status;
    }

    public function delete($object, $id)
    {
        $url = "{$this->instance_url}/services/data/v42.0/sobjects/{$object}/{$id}";

        $client = new Client();
        $request = $client->request('DELETE', $url, [
            'headers' => [
                'Authorization' => "OAuth {$this->access_token}",
            ]
        ]);

        $status = $request->getStatusCode();

        if ($status != 204) {
            throw new SalesforceException(
                "Error: call to URL {$url} failed with status {$status}, response: {$request->getReasonPhrase()}"
            );
        }

        return true;
    }
}
