# VCTCC — Vital Clinic

Sistema web de gestão de consultas para administradores e médicos. Esta versão foi **desacoplada do MySQL local**: o projeto funciona em modo demonstração usando um arquivo JSON e possui um cliente preparado para conversar com uma API central, que poderá ser compartilhada pelo site e pelo aplicativo móvel.

> O objetivo desta versão é separar a interface da origem dos dados. O site continua navegável durante o desenvolvimento, enquanto o banco definitivo poderá ficar em um único backend acessado por API.

## Modos de funcionamento

| Modo | Configuração | Comportamento |
| --- | --- | --- |
| Demonstração | `VCTCC_DATA_MODE=demo` ou configuração padrão | Usa `data/demo-state.json`. Não depende de MySQL nem XAMPP para o banco. |
| API central | `VCTCC_DATA_MODE=api` e `VCTCC_API_URL=https://...` | Busca e salva o estado pela API central no endpoint `v1/state`. |

O modo padrão é `demo`, para que a aplicação continue funcionando imediatamente após ser copiada para o servidor local. O arquivo JSON serve apenas para protótipo e desenvolvimento; não deve ser usado como banco de produção ou acessado diretamente por usuários.

## Execução local

O projeto continua sendo uma aplicação PHP. Para executar localmente, copie a pasta `VCTCC-main` para o diretório público do Apache, por exemplo:

```text
C:\xampp\htdocs\VCTCC-main
```

Inicie somente o **Apache** no XAMPP e acesse:

```text
http://localhost/VCTCC-main/
```

O MySQL não é mais necessário para o modo demonstração. Caso o PHP seja executado por outro servidor web, o diretório `data` precisa ter permissão de escrita para que o estado JSON seja atualizado.

## Acesso de demonstração

| Perfil | E-mail | Senha |
| --- | --- | --- |
| Administrador | `admin@clinica.local` | `password` |
| Médico | `medico@clinica.local` | `password` |

Os pacientes não possuem login. Existe um registro de paciente de demonstração somente para que o médico e o administrador possam visualizar uma consulta e um prontuário no fluxo interno.

## Configurar a API central

Quando a API compartilhada pelo aplicativo e pelo site estiver disponível, configure as variáveis de ambiente do PHP:

```text
VCTCC_DATA_MODE=api
VCTCC_API_URL=https://seu-dominio.example/api
VCTCC_API_TOKEN=seu-token-de-servico
VCTCC_API_TIMEOUT=8
```

Também é possível definir os valores diretamente em `app/config.php`, embora as variáveis de ambiente sejam preferíveis para não colocar tokens no código.

O cliente atual utiliza o contrato inicial abaixo:

| Método | Caminho | Finalidade |
| --- | --- | --- |
| `GET` | `/v1/state` | Retorna o estado compatível com o protótipo. |
| `PUT` | `/v1/state` | Persiste o estado enviado pelo site. |

A resposta pode ser o objeto diretamente ou um objeto com a propriedade `data`:

```json
{
  "data": {
    "clinics": [],
    "specialties": [],
    "users": [],
    "doctors": [],
    "doctor_schedules": [],
    "schedule_blocks": [],
    "appointment_slots": [],
    "appointments": [],
    "notifications": [],
    "medical_records": [],
    "payments": []
  }
}
```

Esse endpoint de estado é uma ponte de desenvolvimento. Para produção, recomenda-se evoluir a API para endpoints específicos, como `/v1/auth/login`, `/v1/doctors`, `/v1/appointments`, `/v1/schedules`, `/v1/medical-records` e `/v1/payments`, com autenticação e permissões no servidor. O aplicativo móvel e o site devem acessar a API, e nunca o banco central diretamente.

## Estrutura da integração

```text
Aplicativo móvel ─┐
                  ├── API central ─── Banco de dados único
Site PHP/PWA ─────┘
```

O banco central deve ficar protegido no servidor da API. O site e o aplicativo devem compartilhar regras de autenticação, validação, permissões e formato de respostas no backend.

## PWA

O projeto inclui:

| Arquivo | Função |
| --- | --- |
| `manifest.webmanifest` | Nome, ícone, cores, escopo e modo instalável da aplicação. |
| `service-worker.js` | Cache da casca visual e fallback básico quando a rede falha. |
| `assets/js/app.js` | Registro automático do service worker em `localhost` ou HTTPS. |
| `assets/brand/` | Pasta única para as imagens da marca. |

Para o navegador oferecer a instalação como aplicativo, sirva o projeto por `localhost` ou HTTPS. O cache não armazena respostas da API nem dados sensíveis; as operações que dependem do backend continuam exigindo conexão.

## Logos centralizadas

As imagens da marca estão concentradas em:

```text
assets/brand/vital-clinic-logo.svg
assets/brand/vital-clinic-mark.svg
```

Os templates usam esses caminhos diretamente. Para trocar a identidade visual, substitua os arquivos mantendo os mesmos nomes e formatos ou atualize os caminhos em `app/config.php`, `index.php` e `pages/auth/login.php`.

## Organização principal

| Caminho | Responsabilidade |
| --- | --- |
| `app/api_client.php` | Cliente HTTP para o backend central. |
| `app/repository.php` | Repositório de demonstração, estado compartilhado e ponte de persistência. |
| `app/auth.php` | Sessão e autenticação de administrador/médico. |
| `data/demo-state.json` | Dados locais do modo demo. |
| `manifest.webmanifest` | Configuração PWA. |
| `service-worker.js` | Cache offline da interface. |

## Observações para o TCC

A arquitetura deixa clara a separação entre **frontend**, **API** e **banco de dados**. O site funciona de forma independente em modo demonstração, mas a fonte oficial dos dados deverá ser a API central quando o aplicativo e o site forem integrados. Essa separação facilita explicar no TCC que o banco não é acessado diretamente pelo cliente.

Antes de publicar a API, implemente autenticação por token ou sessão, autorização por perfil, validação de entrada, controle de concorrência, tratamento de erros e CORS restrito aos domínios do projeto. Não coloque senha de banco ou credenciais administrativas no JavaScript ou no aplicativo móvel.

## Referências

[1]: https://developer.mozilla.org/en-US/docs/Web/Progressive_web_apps "MDN — Progressive web apps"

[2]: https://developer.mozilla.org/en-US/docs/Web/API/Service_Worker_API "MDN — Service Worker API"
