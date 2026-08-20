# SITE Vital Clinic

## Execução local (sem banco)

O projeto continua sendo uma aplicação PHP. Para executar localmente, copie a pasta `VCTCC-main` para o diretório público do Apache, por exemplo:

```text
C:\xampp\htdocs\VCTCC-main
```

Inicie somente o **Apache** no XAMPP e acesse:

```text
http://localhost/VCTCC-main/
```

O MySQL não é mais necessário para o modo demonstração. Caso o PHP seja executado por outro servidor web, o diretório `data` precisa ter permissão de escrita para que o estado JSON seja atualizado.

## Usuários genéricos para acesso

| Perfil | E-mail | Senha |
| --- | --- | --- |
| Administrador | `admin@clinica.local` | `password` |
| Médico | `medico@clinica.local` | `password` |

Os pacientes não possuem login. Existe um registro de paciente de demonstração somente para que o médico e o administrador possam visualizar uma consulta e um prontuário no fluxo interno.
