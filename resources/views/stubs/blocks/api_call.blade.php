        $response = $this->apiCall('{{ $params['method'] ?? 'GET' }}', '{!! addslashes($params['url']) !!}');
