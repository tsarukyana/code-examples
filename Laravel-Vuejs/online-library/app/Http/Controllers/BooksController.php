<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;

class BooksController extends Controller
{
    public function search(Request $request)
    {
        if (empty($request->q) || !isset($request->q['searchType'])) {
            return response()->json(['status' => false, 'books' => ['message' => 'Invalid search details']]);
        }

        $search = [];
        if ($request->q['searchType'] === 0) { // search
            if (!empty($request->q['q'])) {
                $search['q'] = $request->q['q'];
            }
        } else if ($request->q['searchType'] === 1) { // advanced search
            if (!empty($request->q['title'])) {
                $search['title'] = $request->q['title'];
            }
            if (!empty($request->q['author'])) {
                $search['author'] = $request->q['author'];
            }
        } else { // isbn search
            if (!empty($request->q['isbn'])) {
                $search['isbn'] = $request->q['isbn'];
            }
        }

        $search['page'] = !empty($request->q['page']) && is_numeric($request->q['page']) ? $request->q['page'] : 1;

        if ($request->q['searchType'] === 0 || $request->q['searchType'] === 1) {
            $url = env('OPEN_LIBRARY_SEARCH_API') . '?' . http_build_query($search);
        } else { // isbn search
            $url = env('OPEN_LIBRARY_BOOKS_API') . '?bibkeys=ISBN:' . $search['isbn'] . '&format=json';
        }

        try {
            $http = new Client;
            $response = $http->get($url);
            $data = json_decode((string) $response->getBody(), true);

            return response()->json(['status' => true, 'books' => $data]);
        } catch (GuzzleException $exception) {
            $response = $exception->getResponse();
            $jsonBody = null;
            if ($response) {
                $jsonBody = json_decode((string) $response->getBody(), true);
            }
            return response()->json(['status' => false, 'books' => $jsonBody]);
        } catch (\Exception $exception) {
            return response()->json(['status' => false,  'books' => ['message' => $exception->getMessage()]]);
        }
    }
}
