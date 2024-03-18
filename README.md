## Table of Contents
- [Requirements](#requirements)
- [Installation](#installation)
- [Laravel Nova](#laravel-nova)
  - [Setting up the Nova API Key](#setting-up-the-nova-api-key)
  - [Nova Access](#nova-access)
- [ChromaDB](#chromadb)
  - [Connecting to ChromaDB](#connecting-to-chromadb)
  - [Authentication](#authentication)
  - [Selecting an Embedding Function](#selecting-an-embedding-function)
  - [Validate ChromaDB Sync](#validate-chromadb-sync)
  - [Sync ChromaDB with relational database](#sync-chromadb-with-relational-database)
  - [Clearing ChromaDB Data](#clearing-chromadb-data)
- [OpenAI](#openai)
  - [Setting up the OpenAI API key](#setting-up-the-openai-api-key)
  - [Registering New Language Models](#registering-new-language-models)
  - [Selecting a Language Model](#selecting-a-language-model)
  - [Configuring Temperature and Max Response Tokens](#configuring-temperature-and-max-response-tokens)
  - [Understanding Pricing: Completion Tokens and Prompt Tokens](#understanding-pricing-completion-tokens-and-prompt-tokens)
- [Application Usage](#application-usage)
  - [Adding a Module to the Application](#adding-a-module-to-the-application)
  - [Adding a ChromaDB Collection](#adding-a-chromadb-collection)
  - [Adding embeddings to a Collection](#adding-embeddings-to-a-collection)
  - [Adding an agent to a Module](#adding-an-agent-to-a-module)


## Requirements
- **PHP: ^8.2**
- **NPM: ^10.2**
- **Composer ^2.5**
- **Docker: ^24.0**

## Installation

**Follow these steps to get the Chatbot up and running on your local machine:**
1. **Clone the repository:**
```
$ git clone https://github.com/BlackyDrum/chatbot.git
```
2. **Navigate to the project directory:**
```
$ cd chatbot
```
3. **Install the dependencies. You will be asked to provide a valid Nova Username and License Key. More information [here](https://nova.laravel.com/docs/installation.html):**
```
$ composer install
```
4. **Create a copy of the .env.example file and rename it to .env. Update the necessary configuration values:**
```
$ cp .env.example .env
```
5. **Generate an application key:**
```
$ php artisan key:generate
```
6. **Run the database migrations:**
```
$ php artisan migrate
```
7. **Install JavaScript dependencies:**
```
$ npm install
```
8. **Build the assets:**
```
$ npm run build
```
9. **Run the ChromaDB Docker container:**
```
$ docker-compose up -d
```
10. **(Optional) Seed the database with demo data:**
```
$ php artisan db:seed --class=DemoSeeder
```
11. **Start the development server:**
```
$ php artisan serve
```
12. **Visit http://localhost:8000 in your web browser to access the application.**

## Laravel Nova
### Setting up the Nova API Key
Our application leverages ``Laravel Nova`` for dashboard management functionalities. To use ``Nova``, a valid ``license key`` is required.
1. **Provide License Key**: Insert your ``Nova license key`` into your ``.env`` file as follows:
```env
NOVA_LICENSE_KEY=nova_license_key
```
2. **Validate License Key**: After setting the ``license key``, ensure its validity by executing the following command in your terminal:
```
$ php artisan nova:check-license
```

### Nova Access
In order to have full access to the dashboard, you need to manually set the ``admin`` column in the ``users`` table to true:
```sql
UPDATE users SET admin=true WHERE id={USER_ID};
```

## ChromaDB
### Connecting to ChromaDB
To establish a connection with the ``ChromaDB`` service, configure the required environment variables as follows:
```env
CHROMA_HOST=http://localhost
CHROMA_PORT=8080
CHROMA_DATABASE=DBWT
CHROMA_TENANT=DBWT
```
- ``CHROMA_HOST``: The URL or IP address where the ChromaDB server is hosted (default: **http://localhost**).
- ``CHROMA_PORT``: The port on which ChromaDB is listening (default: **8080**).
- ``CHROMA_DATABASE``: The name of the database you wish to access.
- ``CHROMA_TENANT``: Your tenant identifier within ChromaDB, typically the same as the database name.

### Authentication
To authenticate with ``ChromaDB`` using static token-based authentication, configure the following environment variable with your secret token:
```env
CHROMA_SERVER_AUTH_CREDENTIALS="my-secret-token"
```
Ensure that the secret token matches the one defined within the ``ChromaDB`` container environment. 

### Selecting an Embedding Function
Our app supports two embedding providers: ``OpenAI`` and ``Jina``. To select your preferred provider, set the following environment variable accordingly:
```env
CHROMA_EMBEDDING_FUNCTION="jina" # jina or openai
```
Additionally, you must specify the ``embedding model`` and ``API Key`` corresponding to your chosen provider:
- For ``OpenAI``:
```env
OPENAI_API_KEY=openai_api_key
OPENAI_EMBEDDING_MODEL=text-embedding-ada-002
```
- For ``Jina``:
```env
JINA_API_KEY=jina_api_key
JINA_EMBEDDING_MODEL=jina-embeddings-v2-base-de
```

### Validate ChromaDB Sync
Our dashboard only provides a view into the relational database. To ensure that ``ChromaDB`` is correctly synchronized with this database, you can verify the sync status using the following command:
```
$ php artisan chroma:check
```
This command checks if ``ChromaDB's`` data is in sync with the relational database, helping maintain data integrity and consistency.

### Sync ChromaDB with relational database
Should the validation indicate a discrepancy, or if you wish to manually synchronize ``ChromaDB`` with the relational database, execute the command below:
```
$ php artisan chroma:sync
```
This step is crucial, especially in scenarios where ``ChromaDB`` holds data that has not yet been updated or reflected in the relational database. It ensures both databases are aligned.

### Clearing ChromaDB Data
To completely remove all stored data related to ``ChromaDB``, including ``embeddings`` and ``collections``, you can use the following command:
```
$ php artisan chroma:destroy
```

## OpenAI
### Setting up the OpenAI API key
Our application utilizes the ``OpenAI`` API to power the ``conversation`` functionalities. To enable these features, it is necessary to provide your ``OpenAI API key``.
```env
OPENAI_API_KEY=openai_api_key
```

### Registering New Language Models
Our application supports a selection of default language models. If you need to integrate a new model, you can do so by adding a new model file in the specified directory within our application.<br><br>
**How to add a new model**:
1. **Create a New Model File**: Navigate to ``app/Nova/Metrics/Openai/Models`` and create a new ``PHP`` file for your model. Use a name that reflects the model's default name, for example, ``gpt_3_5_turbo_0125.php``.
2. **Define the Model Class**: Populate your new file with the following template, adjusting the properties to match the specifics of the new model you are registering:
```php
<?php

namespace App\Nova\Metrics\Openai\Models;

use App\Nova\Metrics\Openai\Model;

class gpt_3_5_turbo_0125 extends Model
{
    // Define the price in dollars per million tokens
    public float $input = 0.50; // Input token price
    public float $output = 1.50; // Output token price

    // Model identifier
    public $name = 'gpt-3.5-turbo-0125';

    // Optional: Specify the UI width for the Nova dashboard (e.g., '1/2')
    public $width = '1/2';
}
```
Replace the ``class name`` and properties (``$input``, ``$output``, ``$name``, and optionally ``$width``) with the appropriate values for the new model.

3. **Register the Model**: After you have created and set up your new model file, the next step is to register this model so it becomes available for use within our application. This is done by adding it to the list of models in the ``app/Nova/Dashboards/OpenAI.php`` file.
```php
public static function getAllModels()
{
    return [
        new gpt_3_5_turbo_0125(),
         ...
    ];
}
```

### Selecting a Language Model
To configure which language model is used for ``conversations`` within our application, you need to specify your choice in the ``.env`` file:
```env
OPENAI_LANGUAGE_MODEL=gpt-3.5-turbo-0125
```

### Configuring Temperature and Max Response Tokens
In our application, both the ``temperature`` and ``max_response_tokens`` settings can be customized for each user, allowing for personalized interaction experiences with the ``AI``. These settings are adjustable directly in the database by modifying specific user columns.<br><br>
**Temperature**
- **What It Is**: The ``temperature`` setting controls the AI's ``creativity`` or ``randomness`` level when generating responses. A higher ``temperature`` results in more varied and creative responses, while a lower ``temperature`` produces more predictable and conservative outputs.
- **How to Configure**: To adjust this setting for a user, locate the ``temperature`` column in the user's database record. This value should be a ``decimal`` between 0 and 1, where, for example, 0.5 represents a moderate level of creativity, and 0.9 indicates a high level of creativity.
<br><br>

**Max Response Tokens**
- **What It Is**: The ``max_response_tokens`` setting determines the maximum length of the AI-generated ``response``, measured in ``tokens``. A ``token`` can be a word or part of a word, so this setting effectively controls how verbose or concise the responses will be.
- **How to Configure**: To change this setting, find the ``max_response_tokens`` column in the user's database record. This value should be an ``integer``, reflecting the maximum number of ``tokens`` allowed in a response. For instance, setting this to 150 limits responses to approximately 150 ``tokens``, which could equate to a few sentences or a short paragraph, depending on the language and content.

### Understanding Pricing: Completion Tokens and Prompt Tokens
When integrating ``OpenAI's`` language models into our application, it's important to be aware of how the service's pricing works. Costs are primarily based on the number of ``tokens`` processed, which are divided into two categories: ``completion tokens`` and ``prompt tokens``. Accessing the cost details for each model is straightforward via the ``OpenAI`` dashboard in ``Laravel Nova``.
- **Prompt Tokens**: Prompt tokens are those that form the ``input`` given to the language model. These include the question the user is asking or any prelude information that sets the context for the model's response, e.g our ``embeddings``.
- **Completion Tokens**: Completion tokens are generated by the language model as part of its ``response`` to the prompt. The model's output, whether it's answering a question, continuing a text, or generating something new, consists of these tokens.


## Application Usage
To make the application fully operational, certain preliminary data entries are necessary.
### Adding a Module to the Application
``Modules`` in our application mirror the structure of courses at ``FH Aachen``. To add a module, you'll need to populate entries in the ``modules`` table with specific details:
- **Name**: This is the ``name`` of the module or course as it is officially referred to at ``FH Aachen``.
- **Ref ID**: Each course at ``FH Aachen`` has a unique ``reference ID``, often found in ``ILIAS``. This ID must be specified to create a clear linkage between the application module and its real-world counterpart in ``ILIAS``.

### Adding a ChromaDB Collection
In our application, each module should have an associated collection within ``ChromaDB``. Collections hold ``embeddings``, which are contextual representations of documents or data points. To ensure effective management and retrieval of these ``embeddings``, you'll need to create entries in the ``collections`` table for each module:
- **Name**: The ``name`` of the collection. It's recommended to use a name that clearly identifies the associated module or the type of embeddings it contains.
- **Max Results**: This value specifies the ``maximum number of documents`` that can be embedded for a single prompt within this collection.
- **Module**: This field links the collection to its corresponding ``module``.

### Adding embeddings to a Collection
With the ``collections`` in place, the next step is to populate them with ``embeddings``. ``Embeddings`` are created by processing ``documents`` or ``files``, which can then be retrieved based on ``semantic similarity`` to queries. Our application supports uploading files in various formats including ``.pptx``, ``.json``, ``.md``, and ``.txt``. Special attention should be paid to ``.json`` and ``.md`` files, as they require a specific format to ensure successful embedding:
- ``json``
```json
{
  "success": true,
  "content": {
    "0": {
      "0": "This is a title from the first slide",
      "1": "This is a first paragraph on the first slide",
      "2": "This is a second paragraph on the first slide",
      "content": [
        "This is a first paragraph on the first slide",
        "This is a second paragraph on the first slide"
      ],
      "title": "This is a title from the first slide"
    },
    "1": {
      "0": "This is a title from the second slide",
      "1": "This is a first paragraph from the second slide",
      "content": [
        "This is a first paragraph from the second slide"
      ],
      "title": "This is a title from the second slide"
    }
  }
}
```
- ``md``
```md
# This is a title from the first slide
	
This is a first paragraph on the first slide This is a second paragraph on the first slide
---

# This is a title from the second slide
	
This is a first paragraph from the second slide
---
```

### Adding an agent to a Module
In our application, ``agents`` are responsible for shaping how responses from ``OpenAI`` are structured and delivered for each ``module``. A ``module`` can have multiple ``agents``, but only one can be set as ``active`` at any given time. When adding an ``agent``, you'll need to provide information for the following columns:
- **Name**: The ``identifier`` or ``title`` for the agent. Choose a name that clearly represents its role or the type of responses it's configured to provide.
- **Instructions**: Detailed ``guidelines`` that the agent follows to generate responses. This should align with the module's content and objectives.
- **Module**: The specific ``module`` this agent is associated with.
- **Active**: A boolean value indicating whether the agent is currently active. Remember, within a module, only one agent can be active at any time.

**Example Instructions**:
```
You are a helpful university tutor providing aid for students tasked with programming relational database based web applications with php. Always explain the code snippets you send and try to provide sources where to learn more on that subject. If in doubt, do not answer with code and ask to clarify the prompt!
```
