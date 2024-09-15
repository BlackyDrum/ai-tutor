<div align="center">

<p>
    <img src="https://github.com/BlackyDrum/chatbot/assets/111639941/94732ba5-7ee2-4a06-9843-5050953676fd" />
</p>

<p>
    <img width="400" src="https://github.com/BlackyDrum/chatbot/assets/111639941/bf33ed0e-7334-463f-bb18-82e46c15be6d">
</p>

<p>
    <img src="https://img.shields.io/badge/Laravel-FF2D20?style=for-the-badge&logo=laravel&logoColor=white"> <img src="https://img.shields.io/badge/Vue.js-35495E?style=for-the-badge&logo=vuedotjs&logoColor=4FC08D"> <img src="https://img.shields.io/badge/Tailwind_CSS-   38B2AC?style=for-the-badge&logo=tailwind-css&logoColor=white"> <img src="https://img.shields.io/badge/PostgreSQL-316192?style=for-the-badge&logo=postgresql&logoColor=white">
</p>

</div>

---

## Description

This project is the result of a bachelor thesis project at `FH Aachen` aimed at developing a `chatbot/tutor` to aid students in their studies. It leverages `artificial intelligence` to provide real-time `assistance` and `tutoring`, making it easier for students to understand complex `subjects`, prepare for `exams`, and get instant help with their `coursework`.

## Table of Contents

-   [Requirements](#requirements)
-   [Installation](#installation)
-   [How It Works](#how-it-works)
-   [Laravel Nova](#laravel-nova)
    -   [Setting up the Nova API Key](#setting-up-the-nova-api-key)
    -   [Nova Access](#nova-access)
-   [ChromaDB](#chromadb)
    -   [Connecting to ChromaDB](#connecting-to-chromadb)
    -   [Authentication](#authentication)
    -   [Selecting an Embedding Function](#selecting-an-embedding-function)
    -   [Validate ChromaDB Sync](#validate-chromadb-sync)
    -   [Sync ChromaDB with Relational Ratabase](#sync-chromadb-with-relational-database)
    -   [Clearing ChromaDB Data](#clearing-chromadb-data)
-   [Seeding (Optional)](#seeding)
-   [OpenAI](#openai)
    -   [Setting up the OpenAI API Key](#setting-up-the-openai-api-key)
    -   [Registering New Language Models](#registering-new-language-models)
    -   [Understanding Pricing: Completion Tokens and Prompt Tokens](#understanding-pricing-completion-tokens-and-prompt-tokens)
    -   [Updating the Model for Conversation Titles](#updating-the-model-for-conversation-titles)
-   [Application Usage](#application-usage)
    -   [Adding a Module to the Application](#adding-a-module-to-the-application)
    -   [Adding a ChromaDB Collection](#adding-a-chromadb-collection)
    -   [Adding Embeddings to a Collection](#adding-embeddings-to-a-collection)
    -   [Adding an Agent to a Module](#adding-an-agent-to-a-module)
-   [Application Config Variables](#application-config-variables)
-   [Cache](#cache)
    -   [Managing Cache After Source Code Changes](#managing-cache-after-source-code-changes)
-   [User Authentication via LTI 1.0](#user-authentication-via-lti-10)
    -   [How It Works](#how-it-works-1)
    -   [Setting Up LTI Integration](#setting-up-lti-integration)

## Requirements

-   **PHP: ^8.2**
-   **NPM: ^10.2**
-   **Composer ^2.5**
-   **Docker: ^24.0**
-   **(Recommended) Laravel Idea PHPStorm Plugin**

## Installation

Follow these steps to get the Chatbot up and running on your local machine:

1. Clone the `repository`:

```
$ git clone https://github.com/BlackyDrum/ai-tutor.git
```

2. Navigate to the `project directory`:

```
$ cd ai-tutor
```

3. Install the `dependencies`. You will be asked to provide a valid `Nova Username` and `License Key`. More information [here](https://nova.laravel.com/docs/installation.html):

```
$ composer install
```

4. Create a copy of the `.env.example` file and rename it to `.env`. Update the necessary configuration values.

5. Generate an `application key`:

```
$ php artisan key:generate
```

6. Run the database `migrations`:

```
$ php artisan migrate
```

7. Install JavaScript `dependencies`:

```
$ npm install
```

8. Build the `assets`:

```
$ npm run build
```

9. Run the `ChromaDB` Docker container:

```
$ docker-compose up -d
```

10. Start the `development server`:

```
$ php artisan serve
```

11. Visit `http://localhost:8000` in your web browser to access the application.

## How It Works

Here's a brief overview of the steps involved in processing a `user query`:

1. First, the application takes the question the user asked
2. It then uses `ChromaDB` (our vector database) to search for `information`/`documents` that match or are relevant to the question
3. Once it finds the relevant information, the application organizes it into a format that is easier to understand for `GPT`
4. This formatted message, along with the user's original message, is sent to `GPT`, which then generates an answer based on the data it received
5. Finally, the application returns the answer back to the user

To see a more detailed explanation with examples, click [here](https://cookbook.openai.com/examples/question_answering_using_embeddings).

## Laravel Nova

### Setting up the Nova API Key

Our application leverages `Laravel Nova` for dashboard management functionalities. To use `Nova`, a valid `license key` is required.

1. **Provide License Key**: Insert your `Nova license key` into your `.env` file as follows:

```env
NOVA_LICENSE_KEY=nova_license_key
```

2. **Validate License Key**: After setting the `license key`, ensure its validity by executing the following command in your terminal:

```
$ php artisan nova:check-license
```

### Nova Access

In order to have full access to the dashboard, you need to manually set the `admin` column in the `users` table to true:

```sql
UPDATE users SET admin=true WHERE id={USER_ID};
```

## ChromaDB

### Connecting to ChromaDB

To establish a connection with the `ChromaDB` service, configure the required environment variables as follows:

```env
CHROMA_HOST=http://localhost
CHROMA_PORT=8080
CHROMA_DATABASE=DBWT
CHROMA_TENANT=DBWT
```

-   `CHROMA_HOST`: The URL or IP address where the ChromaDB server is hosted (default: **http://localhost**).
-   `CHROMA_PORT`: The port on which ChromaDB is listening (default: **8080**).
-   `CHROMA_DATABASE`: The name of the database you wish to access.
-   `CHROMA_TENANT`: Your tenant identifier within ChromaDB, typically the same as the database name.

### Authentication

To authenticate with `ChromaDB` using static token-based authentication, configure the following environment variable with your secret token:

```env
CHROMA_SERVER_AUTH_CREDENTIALS="my-secret-token"
```

Ensure that the secret token matches the one defined within the `ChromaDB` container environment.

### Selecting an Embedding Function

Our app supports two embedding providers: `OpenAI` and `Jina`. To select your preferred provider, set the following environment variable accordingly:

```env
CHROMA_EMBEDDING_FUNCTION="jina" # jina or openai
```

Additionally, you must specify the `embedding model` and `API Key` corresponding to your chosen provider:

-   For `OpenAI`:

```env
OPENAI_API_KEY=openai_api_key
OPENAI_EMBEDDING_MODEL=text-embedding-ada-002
```

-   For `Jina`:

```env
JINA_API_KEY=jina_api_key
JINA_EMBEDDING_MODEL=jina-embeddings-v2-base-de
```

### Validate ChromaDB Sync

Our dashboard only provides a view into the relational database. To ensure that `ChromaDB` is correctly synchronized with this database, you can verify the sync status using the following command:

```
$ php artisan chroma:check
```

This command checks if `ChromaDB's` data is in sync with the relational database, helping maintain data integrity and consistency.

### Sync ChromaDB with Relational Database

Should the validation indicate a discrepancy, or if you wish to manually synchronize `ChromaDB` with the relational database, execute the command below.
You need to specify the `authoritative` data source by using `--source=chroma` for `ChromaDB` or `--source=relational` for the relational database. The selected source's data will be replicated to the other database, and existing data in the target database will be overwritten or removed.

```
$ php artisan chroma:sync --source=chroma # or --source=relational
```

### Clearing ChromaDB Data

To completely remove all stored data related to `ChromaDB`, including `embeddings` and `collections`, you can use the following command:

```
$ php artisan chroma:destroy
```

## Seeding
(Optional) Seed the database with `demo` data:

```
$ php artisan db:seed
```

Username: `admin` Password: `P3X8MYcd2BwE7qa3`

> **Note:** Make sure to not use this user in production!

## OpenAI

### Setting up the OpenAI API Key

Our application utilizes the `OpenAI` API to power the `conversation` functionalities. To enable these features, it is necessary to provide your `OpenAI API key`.

```env
OPENAI_API_KEY=openai_api_key
```

### Registering New Language Models

Our application supports a selection of default language models. If you need to integrate a new model, you can do so by adding a new model file in the specified directory within our application.<br><br>
**How to add a new model**:

1. **Create a New Model File**: Navigate to `app/Nova/Metrics/Openai/Models` and create a new `PHP` file for your model. Use a name that reflects the model's default name, for example, `gpt_3_5_turbo_0125.php`.
2. **Define the Model Class**: Populate your new file with the following template, adjusting the properties to match the specifics of the new model you are registering:

```php
<?php

namespace App\Nova\Metrics\Openai\Models;

use App\Nova\Metrics\Openai\Model;

class gpt_3_5_turbo_0125 extends Model
{
    // Define the price in dollars per million tokens
    public float $input = 0.5; // Input token price
    public float $output = 1.5; // Output token price

    // Model identifier
    public $name = "gpt-3.5-turbo-0125";

    // Optional: Specify the UI width for the Nova dashboard (e.g., '1/2')
    public $width = "1/2";
}
```

Replace the `class name` and properties (`$input`, `$output`, `$name`, and optionally `$width`) with the appropriate values for the new model.

3. **Register the Model**: After you have created and set up your new model file, the next step is to register this model so it becomes available for use within our application. This is done by adding it to the list of models in the `app/Nova/Dashboards/OpenAI.php` file.

```php
public static function models()
{
    return [
        new gpt_3_5_turbo_0125(),
        ...
    ];
}
```

### Understanding Pricing: Completion Tokens and Prompt Tokens

When integrating `OpenAI's` language models into our application, it's important to be aware of how the service's pricing works. Costs are primarily based on the number of `tokens` processed, which are divided into two categories: `completion tokens` and `prompt tokens`. Accessing the cost details for each model is straightforward via the `OpenAI` dashboard in `Laravel Nova`.

-   **Prompt Tokens**: Prompt tokens are those that form the `input` given to the language model. These include the question the user is asking or any prelude information that sets the context for the model's response, e.g our `embeddings`.
-   **Completion Tokens**: Completion tokens are generated by the language model as part of its `response` to the prompt. The model's output, whether it's answering a question, continuing a text, or generating something new, consists of these tokens.

### Updating the Model for Conversation Titles

To make conversations easier to navigate and understand for users, our app automatically generates `titles` for each conversation. This is done by sending the first messages of a conversation to `OpenAI`, which then creates a short and concise `title`. You can choose which `OpenAI` model is used for this by updating the `.env` file:

```env
OPENAI_CONVERSATION_TITLE_CREATOR_MODEL=gpt-4-1106-preview
```

Just replace `gpt-4-1106-preview` with the model you prefer. Of course, the cost of generating these titles is included in the `OpenAI` usage, visible on the `OpenAI` dashboard.

## Application Usage

To make the application fully operational, certain preliminary data entries are necessary.

### Adding a Module to the Application

`Modules` in our application mirror the structure of courses at `FH Aachen`. To add a module, you'll need to populate entries in the `modules` table with specific details:

-   **Name**: This is the `name` of the module or course as it is officially referred to at `FH Aachen`.
-   **Ref ID**: Each course at `FH Aachen` has a unique `reference ID`, often found in `ILIAS`. This ID must be specified to create a clear linkage between the application module and its real-world counterpart in `ILIAS`.

### Adding a ChromaDB Collection

In our application, each module should have an associated collection within `ChromaDB`. Collections hold `embeddings`, which are contextual representations of documents or data points. To ensure effective management and retrieval of these `embeddings`, you'll need to create entries in the `collections` table for each module:

-   **Name**: The `name` of the collection. It's recommended to use a name that clearly identifies the associated module or the type of embeddings it contains.
-   **Max Results**: This value specifies the `maximum number of documents` that can be embedded for a single prompt within this collection.
-   **Module**: This field links the collection to its corresponding `module`.
-   **Active**: A boolean value indicating whether the collection is currently used to `retrieve` documents for a specific module. Any new conversation will utilize this active collection, while `pre-existing` conversations continue with the collection they were `initially` assigned. Within a module, only one collection can be `active` at any time.

### Adding Embeddings to a Collection

With the `collections` in place, the next step is to populate them with `embeddings`. `Embeddings` are created by processing `documents` or `files`, which can then be retrieved based on `semantic similarity` to queries. Our application supports uploading files in various formats including `.pptx`, `.json`, `.md`, `.txt`, `.pdf` and `.zip`. Special attention should be paid to `.json` and `.md` files, as they require a specific format to ensure successful embedding:

-   `json`

```json
{
    "content": [
        {
            "content": [
                "This is a first paragraph on the first slide",
                "This is a second paragraph on the first slide"
            ],
            "title": "This is a title from the first slide"
        },
        {
            "content": ["This is a first paragraph from the second slide"],
            "title": "This is a title from the second slide"
        }
    ]
}
```

-   `md`

```md
# This is a title from the first slide

This is a first paragraph on the first slide
This is a second paragraph on the first slide
---

# This is a title from the second slide

This is a first paragraph from the second slide
---
```

> To upload several files simultaneously, compress the files into a `zip` archive and then upload the `zip`.

### Adding an Agent to a Module

In our application, `agents` are responsible for shaping how responses from `OpenAI` are structured and delivered for each `module`. A `module` can have multiple `agents`, but only one can be set as `active` at any given time. When adding an `agent`, you'll need to provide information for the following columns:

-   **Name**: The `identifier` or `title` for the agent. Choose a name that clearly represents its role or the type of responses it's configured to provide.
-   **Instructions**: Detailed `guidelines` that the agent follows to generate responses. This should align with the module's content and objectives.
-   **Module**: The specific `module` this agent is associated with.
-   **Active**: A boolean value indicating whether the agent is currently `active`. Any new conversation will utilize this active agent, while `pre-existing` conversations continue with the agent they were `initially` assigned. Remember, within a module, only one agent can be active at any time.
-   **OpenAI Language Model**: Specify which `OpenAI` language model should be used for generating `responses`.
-   **Max Messages Included**: Limits the number of `previous messages` considered for context in an ongoing conversation.
-   **Temperature**: The `temperature` setting controls the AI's `creativity` or `randomness` level when generating responses. A higher `temperature` results in more varied and creative responses, while a lower `temperature` produces more predictable and conservative outputs.
-   **Max Response Tokens**: The `max_response_tokens` setting determines the maximum length of the AI-generated `response`, measured in `tokens`. A `token` can be a word or part of a word, so this setting effectively controls how verbose or concise the responses will be.

**Example Instructions**:

```
You are a helpful university tutor providing aid for students tasked with programming relational database based web applications with php. Always explain the code snippets you send and try to provide sources where to learn more on that subject. If in doubt, do not answer with code and ask to clarify the prompt!
```

## Application Config Variables

In the `config/chat.php` file, various configuration variables allow you to customize the `chat` functionality:

```php
<?php

return [
    // Sets the daily limit of messages a user can send
    "max_requests" => 100,

    // Alerts the user about their remaining message quota at these levels
    "remaining_requests_alert_levels" => [10, 25, 50],

    // The maximum character count allowed per user message
    "max_message_length" => 4096,

    // Specifies the number of chat messages to load per request for efficient pagination
    // Older messages are fetched automatically as the user scrolls
    "messages_per_page_desktop" => 25,
    "messages_per_page_mobile" => 10,

    // Note: 'max_requests' can be adjusted on a per-user basis in the users table
];
```

## Cache
For optimal performance, our application employs caching mechanisms with `OPCache`. For that, we leverage the `laravel-opcache` package. For detailed information about this package and its features, please visit https://github.com/appstract/laravel-opcache.

### Managing Cache After Source Code Changes

Whenever modifications are made to the source code, it's necessary to `manually` clear and reset the cache to ensure the changes are reflected. Execute the following commands to manage the cache:
1. **Clear OPCache**:
```
php artisan opcache:clear
```
2. **Compile Cache**:
```
php artisan opcache:compile
```

## User Authentication via LTI 1.0

Our application implements a secure user registration process through integration with `ILIAS`. This integration is facilitated by `LTI` (Learning Tools Interoperability) version 1.0, ensuring that users can seamlessly `authenticate` and gain access to our application directly from `ILIAS` without the need for separate registration steps.
By using `LTI`, our application can authenticate users based on their existing `ILIAS` credentials.

### How It Works

When a user attempts to access our application from `ILIAS`:

1. **ILIAS sends a launch request** to our application. This request contains the `user's data` and `authentication details`, securely `signed` to ensure data integrity.
2. **Our application validates the signature** on the request using the `consumer_key` and `shared_secret`. This step confirms that the request is indeed from the trusted `ILIAS` platform and has not been tampered with.
3. **User session is initiated** in our application. Once verified, our application creates a new `session` for the user, granting them access without requiring separate login credentials.

### Setting Up LTI Integration

To integrate a new `consumer` with our application using `LTI 1.0`, you need to register the `consumer` by creating a new database entry. This is done using the following artisan command:

```
php artisan lti:add_consumer {name} {consumer_key} {shared_secret} {ref_id}
```

-   `{name}`: The name you wish to assign to the consumer.
-   `{consumer_key}`: A `unique key` that identifies the consumer to our application.
-   `{shared_secret}`: A `secret` shared between our application and the platform to securely sign and validate requests.
-   `{ref_id}`: Unique `Ref ID` for the ILIAS course

**Example**:
```
php artisan lti:add_consumer "Databases & Webtechnologies (32845) - SS 2024" my-consumer-key my-shared-secret 541326
```
