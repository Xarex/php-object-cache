<?php

// json compatibility functions for php4
require_once('json.compat.php');

/**
 * 
 * PHP Socket Cache Server
 * 
 * Implements an Object Cache via PHP Sockets
 * Objects are passed as JSON, and stored to an array
 * 
 * @author gabe@fijiwebdesign.com
 * @copyright http://www.fijiwebdesign.com/
 * @license http://www.opensource.org/licenses/bsd-license.php
 * @version $Id$
 * 
 * Max read is 2082 bytes, so data must be within that limit
 * 
 * @compat PHP4+
 * 
* LICENSE: Redistribution and use in source and binary forms, with or
* without modification, are permitted provided that the following
* conditions are met: Redistributions of source code must retain the
* above copyright notice, this list of conditions and the following
* disclaimer. Redistributions in binary form must reproduce the above
* copyright notice, this list of conditions and the following disclaimer
* in the documentation and/or other materials provided with the
* distribution.
*
* THIS SOFTWARE IS PROVIDED ``AS IS'' AND ANY EXPRESS OR IMPLIED
* WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED WARRANTIES OF
* MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE DISCLAIMED. IN
* NO EVENT SHALL CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT,
* INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING,
* BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS
* OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND
* ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR
* TORT (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE
* USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH
* DAMAGE.
* 
 */
class Socket_Cache_Server {
	
	function Socket_Cache_Server($domain, $port = 9801) {
		if ($domain && $port) {
			$this->create($domain, $port);
		}
	}
	
	/**
	 * Create a Socket Server on the given domain and port
	 * @param $domain String
	 * @param $port Float[optional]
	 */
	function create($domain, $port = 9801) {
		$this->socket = socket_create(AF_INET,SOCK_STREAM,SOL_TCP);
		socket_bind($this->socket, $domain, $port);
		socket_listen($this->socket);
		socket_set_block($this->socket);
	}
	
	/**
	 * Set a cache value
	 * @return Bool
	 * @param $name String Index to save Object to
	 * @param $value Object Object Value
	 */
	function listen() {
		
		// storage array
		$store = array();
		$socket =& $this->socket;
		$clients = array($socket);
		
		while(true)
		{
			$reads = $clients;
			
		    if(socket_select($reads, $write = NULL, $except = NULL, 60) > 0)
		    {
				
				// read each readable client
		        foreach($reads as $i=>$read) 
				{
					// reference to client
					$client = $clients[array_search($read, $clients)];
					
					// server is readable, meaning client wants to connect
					if ($read == $socket)
					{
						// accept the client, and add him to the $clients array
			            $clients[] = $newsock = socket_accept($socket);
			           
						// next
						continue;
						
					} 
					else if (strlen(($data = trim(@socket_read($read, 2082, PHP_BINARY_READ)))) > 0)
					{
						
						$req = json_decode($data);
						
						//echo "client $client wrote: $data \n";
						
						if (isset($req->cmd)) {
							switch ($req->cmd) 
							{
								case 'set':
								$store[$req->name] = $req->value;
								socket_write($client, 'ok');
								break;
								
								case 'get': 
								$value = isset($store[$req->name]) ? $store[$req->name] : null;
								socket_write($client, json_encode($value));
								break;
								
								case 'dump':
								socket_write($client, json_encode($store));
								break;
							}
						}
					}
					else
					{
						// client has disconnected
						//echo "client $client disconnected\n";
						socket_close($client);
						unset($clients[array_search($read, $clients)]);
					}
				}
				
		    }
		}
	}
	
	/**
	 * Close the Socket Cache Server
	 */
	function close() {
		socket_close($this->socket);
	}
	
}



?>