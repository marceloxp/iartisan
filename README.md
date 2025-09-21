# IArtisan

> Craft Artisan commands with AI-powered ease

![screenshot](https://raw.githubusercontent.com/marceloxp/iartisan/refs/heads/main/images/illustration.png)

![Packagist Version](https://img.shields.io/packagist/v/marceloxp/iartisan)
![License](https://img.shields.io/github/license/marceloxp/iartisan)

IArtisan is a command-line tool that uses the Google Gemini API to suggest `php artisan` commands for Laravel 12 or PHP Filament projects from natural language prompts. Whether you’re a beginner or an experienced developer, IArtisan translates your intent into ready-to-run commands.

---

## ✨ Features

* **Natural language prompts**: describe what you want in plain English and get the equivalent `php artisan` command.
* **Filament support**: use `--filament3` or `--filament4` to generate Filament-specific commands.
* **Optional execution**: if you are inside a Laravel project (with an `artisan` file present), you can confirm and execute the generated command directly.
* **Configurable AI model**: choose which Gemini model to use (`gemini-2.5-flash`, `gemini-pro`, etc.) via `config:set`.
* **Secure configuration**: API keys are managed via environment variables or persisted settings.
* **Clean CLI**: no irrelevant Symfony commands or options, just the essentials.

---

## 🚀 Installation

1. **Install via Composer**:

   ```bash
   composer global require marceloxp/iartisan
   ```

2. **Set up your Gemini API key**:

   ```bash
   export GEMINI_API_KEY=your-api-key
   ```

   or:

   ```bash
   export IARTISAN_GEMINI_KEY=your-api-key
   ```

3. **Verify**:

   ```bash
   iartisan --help
   ```

---

## 🛠 Usage

### Basic prompt

```bash
iartisan create a model Post with migration and controller
```

Output:

```
Generated Command:
php artisan make:model Post -m -c
```

### Filament support

```bash
iartisan --filament4 make a filament page for dashboard
```

Output:

```
Generated Command:
php artisan make:filament-page Dashboard
```

### Configuration

Set a custom Gemini model:

```bash
iartisan config:set GEMINI_MODEL=gemini-pro
```

Clear a configuration:

```bash
iartisan config:clear gemini_model
```

---

## 📚 Examples

* Create a migration:

  ```bash
  iartisan create a migration to add status column to users table
  ```
* Generate a Filament resource:

  ```bash
  iartisan --filament3 make a filament resource for User
  ```
* Run migrations (with confirmation inside a Laravel project):

  ```bash
  iartisan run database migrations
  ```

---

## 📦 Requirements

* PHP 8.1+
* Composer
* Laravel 12 (for command execution)
* Google Gemini API key

---

## 🤝 Contributing

Contributions are welcome! Please fork the repository and submit a pull request. Follow PSR-12 standards and include relevant tests.

---

## 📄 License

MIT — see [LICENSE](LICENSE).

---

## 📬 Support

For issues or feature requests, please open an issue on the [GitHub repository](https://github.com/marceloxp/iartisan).
For questions, contact Marcelo at **[marceloxp@gmail.com](mailto:marceloxp@gmail.com)**.
