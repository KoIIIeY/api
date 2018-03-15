API doc
=======

**Auth token header** Authorization: Bearer {api_token}

Auth api
========

Login
-----
- method post
- url    /api/v1/auth/login
- params email, password
- return {api_token: 'Your api token'}

Register
--------
- method post
- url    /api/v1/auth/register
- params email, name, password, password_confirmation
- return {api_token: 'Your api token'}

Social
--------
- method post
- url    /api/v1/auth/social/{social_provider_name}
- params client_id, code
- return {api_token: 'Your api token'}

Get current user
--------
- method get
- url    /api/v1/auth/current
- params _with_
- return {user json}

Entities api
============

Get entities paginator
----------------------
- method get
- url    /api/v1/{entity_class_basename}
- params _filter_, _with_, _scope_, _per_page_, _page_
- return {total: 1,per_page: 30,current_page: 1,last_page: 1,next_page_url: null,prev_page_url: null,from: 1,to: 1,data: []}

Create entity
-------------
- method post
- url    /api/v1/{entity_class_basename}
- params entity_attributes
- return {created entity json}


==

example: 

attrs[a] = 1

attrs[b] = 2

attrs[c] = [a => 1, b => 2] <<-- relation C saved in one request with host entity

Get one entity
--------------
- method get
- url    /api/v1/{entity_class_basename}/{id}
- params _with_
- return {entity json}

Update entity
-------------
- method put
- url    /api/v1/{entity_class_basename}/{id}
- params entity_attributes
- return {updated entity json}

Destroy entity
--------------
- method delete
- url    /api/v1/{entity_class_basename}/{id}
- return empty json object {}


Relations api
=============

Get entity relation
-------------------
- method get
- url    /api/v1/{parent_class_basename}/{id}/{relation_name}
- params _filter_, _with_, _scope_
- return {relation json} object for has one relation and array for has many

Create related entity
---------------------
- method post
- url    /api/v1/{parent_class_basename}/{id}/{relation_name}
- params entity_attributes
- return {created entity json}

Get one related entity
----------------------
- method get
- url    /api/v1/{parent_class_basename}/{id}/{relation_name}/{id}
- params _with_
- return {entity json}

Update related entity
---------------------
- method put
- url    /api/v1/{parent_class_basename}/{id}/{relation_name}/{id}
- params entity_attributes
- return {updated entity json}

Destroy related entity
----------------------
- method delete
- url    /api/v1/{parent_class_basename}/{id}/{relation_name}/{id}
- return empty json object {}



============================

Static methods call
-------------------

Example: /api/v1/{entity_class_basename}/call/{method_name}/{params}




** INSTALLATION **
------------------

<ol>
<li>composer require koiiiey/api</li>
<li>
    in config/app.php
    <ol>
        <li>providers => [
        Koiiiey\Api\ApiServiceProvider::class,
        ]</li>
    </ol>
</li>
<li>
Call 
<bold>php artisan vendor:publish --tag=guestFile</bold>
it will copy Guest.php model to /app/ directory. 
\App\Guest is class that uses for not-authorized users.
</li>
<li>Configure your auth by [https://laravel.com/docs/5.6/authorization]</li>
</ol> 

