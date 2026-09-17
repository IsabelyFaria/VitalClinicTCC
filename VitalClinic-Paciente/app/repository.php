<?php

// Essa lista guarda o NOME de todas as tabelas que nosso código tem permissão de mexer.
// É tipo uma "lista de convidados": só quem está escrito aqui pode entrar.
const REPOSITORY_TABLES = [
    'clinics', 'specialties', 'users', 'doctors', 'doctor_schedules',
    'schedule_blocks', 'appointment_slots', 'appointments',
    'medical_records', 'payments', 'notifications', 'password_resets',
];

// Essa função só confere se o nome de tabela que chegou é um nome permitido. Não devolve nada (void).
function repo_assert_table(string $table): void
{
    // in_array($table, REPOSITORY_TABLES, true) pergunta: "$table está dentro da lista REPOSITORY_TABLES?"
    // o "true" no final pede comparação rígida (compara tipo e valor, não só "parece igual")
    // o "!" na frente inverte a pergunta: "SE NÃO estiver na lista..."
    if (!in_array($table, REPOSITORY_TABLES, true)) {
        // ...aí a gente joga um erro (throw) e a execução para bem aqui, com essa mensagem de aviso
        throw new RuntimeException('Tabela desconhecida: ' . $table);
    }
    // se a tabela for válida, a função só termina aqui, sem fazer mais nada
    // (repara: não tem "return", o tipo dela é "void", ou seja, "essa função não devolve valor nenhum")
}

// Busca uma linha de uma tabela, pelo id dela. Devolve um array com os dados, ou null se não achar nada.
function repository_find(string $table, int $id): ?array
{
    repo_assert_table($table);
    // primeiro confere se pode mexer nessa tabela, se não puder, já para tudo aqui, nem chega na linha de baixo

    $stmt = db()->prepare("SELECT * FROM `$table` WHERE id = ? LIMIT 1");
    // monta o comando SQL: "SELECT * FROM nome_da_tabela WHERE id = ? LIMIT 1"
    // "*" quer dizer "todas as colunas". "?" é um espaço reservado pro valor do id, que ainda vai chegar.
    // prepare() só monta o comando, ainda não roda ele de verdade.

    $stmt->execute([$id]);
    // agora roda o comando de verdade, encaixando $id no lugar daquele "?"

    $row = $stmt->fetch();
    // pega a primeira (e única, por causa do LIMIT 1) linha que veio como resultado

    return $row ?: null;
    // se não achou nenhuma linha, fetch() devolve "false", o "?:" troca esse "false" por "null"
    // (fica mais fácil de checar depois com "if ($usuario) { ... }")
}

// O "C" de CRUD (Create). Insere uma linha nova numa tabela e devolve o id que o banco gerou pra ela.
function repository_append(string $table, array $data): int
{
    repo_assert_table($table);
    // confere se pode mexer nessa tabela

    unset($data['id']);
    //   remove a chave 'id' do array $data, CASO ela exista por acaso.
    //   a gente nunca deixa esse array escolher o id, quem gera o id é o próprio banco, sozinho
    //   (é o AUTO_INCREMENT que vemos no desenho das tabelas)

    $columns = array_keys($data);
    //   pega só os NOMES das colunas (as "chaves" do array).
    //   exemplo: se $data = ['name' => 'Maria', 'email' => 'maria@x.com'],
    //   então $columns vira ['name', 'email']

    $placeholders = implode(', ', array_fill(0, count($columns), '?'));
    //   essa linha faz duas coisas, de dentro pra fora:
    //   1) array_fill(0, count($columns), '?') cria um array com um "?" repetido, uma vez pra cada coluna.
    //      se tem 2 colunas, vira ['?', '?']
    //   2) implode(', ', [...]) junta esse array numa string só, separando por vírgula: "?, ?"

    $columnList = implode('`, `', $columns);
    //   junta os NOMES das colunas com crase+vírgula+crase entre eles: "name`, `email"
    //   (as crases de fora, que já estão escritas na linha de baixo, fecham o "sanduíche")
    //   crase é como o MySQL reconhece "isso aqui é nome de coluna", evita confusão com outras palavras

    $stmt = db()->prepare("INSERT INTO `$table` (`$columnList`) VALUES ($placeholders)");
    //   junta tudo isso e monta o comando final, algo tipo:
    //   INSERT INTO `users` (`name`, `email`) VALUES (?, ?)

    $stmt->execute(array_values($data));
    //   array_values($data) pega só os valores do array, na mesma ordem das colunas: ['Maria', 'maria@x.com']
    //   execute() roda o comando de verdade, encaixando cada valor no "?" correspondente

    return (int) db()->lastInsertId();
    //   pergunta pro banco "qual foi o último id que você acabou de gerar?" e devolve como número inteiro
}

// O "U" de CRUD (Update). Atualiza uma linha que já existe, só nas colunas que a gente mandar mudar.
function repository_replace(string $table, int $id, array $data): void
{
    repo_assert_table($table);
    //   confere se pode mexer nessa tabela

    unset($data['id']);
    //   tira o 'id' do array de mudanças, não faz sentido "atualizar o id pra ele mesmo"

    if (!$data) {
        return;
    }
    //   se depois de tirar o 'id' não sobrou NADA no array (array vazio conta como "falso" em PHP),
    //   não tem o que atualizar, a função para aqui, sem fazer nenhum UPDATE

    $set = implode(', ', array_map(static fn(string $c): string => "`$c` = ?", array_keys($data)));
    //   essa é a linha mais "esquisita", vamos com calma:
    //   array_keys($data) pega os nomes das colunas a mudar, ex: ['name', 'phone']
    //   array_map(...) roda uma mini-função em CADA item desse array e monta um array novo com os resultados
    //   "static fn(string $c): string => "`$c` = ?"" é uma ARROW FUNCTION, um jeito curto de escrever
    //   uma função de uma linha: ela recebe um nome de coluna ($c) e devolve o texto "`coluna` = ?".
    //   Repara que não escrevemos "return", tudo que vem depois do "=>" já é devolvido sozinho.
    //   Exemplo: pra ['name', 'phone'], o resultado do array_map vira ['`name` = ?', '`phone` = ?']
    //   e o implode(', ', ...) junta isso: "`name` = ?, `phone` = ?"

    $stmt = db()->prepare("UPDATE `$table` SET $set WHERE id = ?");
    //  monta o comando final, tipo: UPDATE `users` SET `name` = ?, `phone` = ? WHERE id = ?

    $stmt->execute([...array_values($data), $id]);
    //   array_values($data) pega os VALORES na mesma ordem das colunas: ['Maria', '11999990000']
    //   os "..." (spread operator) "espalham" esses valores soltos dentro de um array novo,
    //   e colocamos $id por último, porque o último "?" do comando (o do WHERE id = ?) precisa dele
    //   no fim, o array vira algo tipo: ['Maria', '11999990000', 4]
}

// Não existe repository_delete() de propósito! Nunca apagamos uma linha de verdade, só trocamos o
// "status" pra 'inactive' ou 'cancelled'. Isso se chama "soft delete" (exclusão suave): o histórico
// nunca se perde, só fica marcado como cancelado/inativo.

// Um "apelido" mais fácil de ler pra "busca um usuário pelo id".
function repository_find_user(int $id): ?array
{
    return repository_find('users', $id);
    //  só chama a função genérica de busca, já dizendo "procura na tabela 'users'"
}

// Pega os dados de um usuário e "gruda" nele o NOME da clínica (não só o número do clinic_id).
function repository_user_with_clinic(array $user): array
{
    $clinic = !empty($user['clinic_id']) ? repository_find('clinics', (int) $user['clinic_id']) : null;
    //   isso é um "if resumido numa linha só", chamado operador ternário: condição ? seVerdadeiro : seFalso
    //   se $user['clinic_id'] tiver algum valor (não vazio, não zero, não nulo):
    //      busca essa clínica na tabela 'clinics'
    //   senão:
    //      $clinic vira null (esse paciente não tem clínica vinculada)

    $user['clinic_name'] = $clinic['name'] ?? null;
    //   cria uma NOVA posição no array $user, chamada 'clinic_name'
    //   se $clinic existir e tiver 'name', usa esse nome; senão (??), usa null

    return $user;
    //  devolve o array $user só que agora com essa informação extra dentro
}

// Atualiza um usuário, e sempre grava também a data/hora da última mudança.
function repository_update_user(int $id, array $changes): void
{
    $changes['updated_at'] = now_sql();
    //   adiciona (ou substitui) a chave 'updated_at' dentro do array $changes,
    //   com a data/hora de agora (a função now_sql() a gente já criou no helpers.php)

    repository_replace('users', $id, $changes);
    //   chama a função de UPDATE genérica, já dizendo "é na tabela 'users'"
}

// Busca um usuário pelo E-MAIL em vez de pelo id, usada no login e no cadastro.
function find_user_by_email(string $email): ?array
{
    $stmt = db()->prepare('SELECT * FROM users WHERE email = ? LIMIT 1');
    //  monta o comando: busca em 'users' onde o email bate com o valor que vamos mandar

    $stmt->execute([strtolower($email)]);
    //  strtolower($email) converte o e-mail pra minúsculo antes de buscar
    //   (assim "Maria@Email.com" e "maria@email.com" são tratados como o mesmo e-mail)

    $row = $stmt->fetch();
    //  pega a linha encontrada (se encontrou)

    return $row ?: null;
    //  se não achou, devolve null; se achou, devolve o array com os dados do usuário
}

// Confere se um e-mail já está cadastrado (usada no cadastro, pra não deixar duplicar).
function email_in_use(string $email): bool
{
    $stmt = db()->prepare('SELECT COUNT(*) FROM users WHERE LOWER(email) = LOWER(?)');
    //   COUNT(*) conta QUANTAS linhas batem com essa condição, em vez de trazer os dados inteiros
    //   LOWER(email) e LOWER(?) convertem os dois lados pra minúsculo antes de comparar

    $stmt->execute([$email]);
    //   executa, encaixando $email no lugar do "?"

    return (int) $stmt->fetchColumn() > 0;
    //   fetchColumn() pega só o primeiro valor do resultado (o número que o COUNT(*) devolveu)
    //   (int) converte esse valor pra número inteiro de verdade
    //   "> 0" transforma isso numa pergunta de SIM/NÃO: "esse número é maior que zero?"
    //   se for (achou pelo menos 1), devolve true (e-mail já em uso); senão, devolve false
}

function next_appointment_for_patient(int $pacienteId): ?array
{
    $sql = "
        SELECT
            a.id,
            a.status,
            a.modality,
            slot.slot_start,
            slot.slot_end,
            medico.name AS doctor_name,
            esp.name AS specialty_name,
            clin.name AS clinic_name
        FROM appointments a
        JOIN appointment_slots slot ON slot.id = a.slot_id
        JOIN doctors doc ON doc.id = a.doctor_id
        JOIN users medico ON medico.id = doc.user_id
        JOIN specialties esp ON esp.id = a.specialty_id
        JOIN clinics clin ON clin.id = a.clinic_id
        WHERE a.patient_id = ?
          AND a.status IN ('pending', 'confirmed')
          AND slot.slot_start >= ?
        ORDER BY slot.slot_start ASC
        LIMIT 1
    ";
    //   vamos por partes, porque é grande:
    //   SELECT a.id, a.status...  - escolhe quais colunas queremos no resultado. O "a."
    //     na frente de cada uma diz DE QUAL TABELA aquela coluna vem (porque várias
    //     tabelas grudadas podem ter colunas de mesmo nome, tipo "id" ou "name", sem
    //     esse prefixo, o banco não saberia qual "name" você quer: o do médico, da
    //     especialidade ou da clínica).

    //   "AS doctor_name"  - apelido pro resultado. Sem isso, a coluna viria só como
    //     "name", e como TEMOS VÁRIAS colunas chamadas "name" nessa consulta (médico,
    //     especialidade, clínica), ficaria impossível saber qual é qual no resultado
    //     em PHP. Com o apelido, cada uma sai com um nome único.

    //   FROM appointments a  - começa pela tabela appointments, e já apelidamos ela de
    //     "a" (só pra não escrever "appointments." toda hora).

    //   JOIN appointment_slots slot ON slot.id = a.slot_id  - "gruda" a tabela de
    //     horários, usando a regra de que o "id" do slot tem que ser igual ao
    //     "slot_id" guardado na consulta, é assim que o banco sabe como ligar as
    //     duas tabelas, linha com linha.

    //   As próximas linhas de JOIN repetem essa mesma ideia, encadeando: da consulta
    //     pro médico (doctors), do médico pro usuário dele (users, que tem o nome),
    //     da consulta pra especialidade, da consulta pra clínica.

    //   WHERE a.patient_id = ?  - filtra só as consultas DESSE paciente.

    //   AND a.status IN ('pending', 'confirmed')  - só consultas que ainda vão
    //     acontecer (não cancelada, não já realizada). IN(...) é um jeito curto de
    //     escrever "status = 'pending' OU status = 'confirmed'".

    //   AND slot.slot_start >= ?  - só horários que ainda não passaram (maior ou
    //     igual a agora)
    //   ORDER BY slot.slot_start ASC  - ordena da data mais próxima pra mais distante
    //     (ASC = ascendente, crescente)

    //   LIMIT 1  - pega só a primeira linha desse resultado ordenado, ou seja, a
    //     consulta futura mais próxima de acontecer

    $stmt = db()->prepare($sql);
    //   prepara a consulta (mesma ideia de sempre: os "?" viram "buracos" que só
    //   depois recebem valor de verdade, evitando injeção de SQL)

    $stmt->execute([$pacienteId, now_sql()]);
    //   preenche os dois "?" na ordem que aparecem: primeiro o id do paciente,
    //   depois a data/hora de agora (usando aquele now_sql() que já criamos)

    $linha = $stmt->fetch();
    //   fetch() (sem "All" no final) pega só uma linha do resultado, já sabemos que
    //   só pode vir 0 ou 1 linha, por causa do LIMIT 1

    return $linha ?: null;
    //   se não achou nenhuma consulta futura, fetch() devolve "false". O operador
    //   Elvis "?:" troca esse "false" por "null", que é mais claro de entender
    //   quando outra parte do código ler isso (null = "não tem próxima consulta")
}

function appointments_for_patient(int $pacienteId, array $statuses, string $ordem = 'ASC', bool $apenasFuturas = false, string $ordenarPor = 'slot.slot_start'): array
{
    // lista de permissão pra essa nova opção: só aceitamos ordenar pela data
    // da consulta ('slot.slot_start', o padrão de sempre) ou pelo momento da
    // última mudança de status ('a.updated_at', usado no Histórico). Nunca
    // deixamos $ordenarPor cair direto no SQL sem passar por essa checagem,
    // porque nome de coluna não dá pra parametrizar com "?", é a mesma
    // lógica de lista de permissão que já usamos ali embaixo pro $ordem
    if (!in_array($ordenarPor, ['slot.slot_start', 'a.updated_at'], true)) {
        $ordenarPor = 'slot.slot_start';
    }

    $ordem = strtoupper($ordem) === 'DESC' ? 'DESC' : 'ASC';
    //   ORDER BY não dá pra parametrizar com "?" (placeholders só valem pra valores,
    //   não pra palavras-chave do SQL como ASC/DESC). Então, em vez de colar
    //   $ordem direto no SQL (o que abriria brecha pra SQL Injection),
    //   a gente confere manualmente: só aceita 'DESC' de verdade, qualquer outra
    //   coisa vira 'ASC', é a mesma ideia de "lista de permissão" que usamos no
    //   repo_assert_table() lá na parte 1 do repository.php

    $interrogacoes = implode(',', array_fill(0, count($statuses), '?'));
    //   array_fill(0, count($statuses), '?') cria um array cheio de "?", repetido
    //   uma vez pra cada status que a gente recebeu. Ex.: se $statuses tiver 2
    //   itens, isso vira ['?', '?']. implode(',', ...) junta esse array numa
    //   string separada por vírgula: "?,?". Isso é necessário porque o "IN (...)"
    //   do SQL precisa de um "?" pra caada valor da lista, e a gente não sabe de
    //   antemão quantos status vão vir, então montamos essa parte dinamicamente

    $sql = "
        SELECT
            a.id,
            a.status,
            a.modality,
            slot.slot_start,
            slot.slot_end,
            medico.name AS doctor_name,
            esp.name AS specialty_name,
            clin.name AS clinic_name
        FROM appointments a
        JOIN appointment_slots slot ON slot.id = a.slot_id
        JOIN doctors doc ON doc.id = a.doctor_id
        JOIN users medico ON medico.id = doc.user_id
        JOIN specialties esp ON esp.id = a.specialty_id
        JOIN clinics clin ON clin.id = a.clinic_id
        WHERE a.patient_id = ?
          AND a.status IN ($interrogacoes)
        ORDER BY $ordenarPor $ordem
    ";
    //   é quase o mesmo JOIN de antes, só trocamos "LIMIT 1" por nada (queremos
    //   TODAS as linhas agora) e "AND a.status IN ('pending','confirmed')" por
    //   "AND a.status IN ($interrogacoes)", os status agora vêm de fora, como
    //   parâmetro, em vez de fixos dentro da função

    $stmt = db()->prepare($sql);

    $stmt->execute([$pacienteId, ...$statuses]);
    //   o "..." (spread operator, já vimos ele na parte 1 do repository.php) pega
    //   o array $statuses e "espalha" cada item dele como um parâmetro separado,
    //   na ordem. Ex.: se $statuses = ['pending', 'confirmed'], isso equivale a
    //   escrever execute([$pacienteId, 'pending', 'confirmed']), preenchendo,
    //   nessa ordem, o "?" do patient_id e os "?,?" do IN(...)

    return $stmt->fetchAll();
    //   fetchAll() (com "All" no final, diferente do fetch() de antes) pega TODAS
    //   as linhas do resultado, devolvendo um array de arrays, um item por consulta
}

function cancel_appointment_patient(int $consultaId, int $pacienteId): void
{
    // Busca a consulta no banco, já trazendo o horário de início (que mora na tabela
    // appointment_slots, lembra do JOIN). Precisamos desse horário
    // pra conferir a regra das 24 horas mais abaixo.
    $sql = "
        SELECT a.id, a.status, a.patient_id, a.slot_id, slot.slot_start
        FROM appointments a
        JOIN appointment_slots slot ON slot.id = a.slot_id
        WHERE a.id = ?
    ";
    $stmt = db()->prepare($sql);
    $stmt->execute([$consultaId]);
    $consulta = $stmt->fetch();

    // Se não achou nenhuma linha com esse id, $consulta vem como "false".
    // Pode acontecer de alguém tentar cancelar um id que não existe (digitando
    // direto na URL, por exemplo), então a gente barra aqui.
    if (!$consulta) {
        throw new RuntimeException('Consulta não encontrada.');
    }

    // SEGURANÇA: confere se essa consulta realmente pertence ao paciente que está
    // pedindo o cancelamento. Sem essa checagem, qualquer pessoa logada poderia
    // cancelar a consulta de outro paciente só trocando o número do id no formulário.
    // (int) garante que estamos comparando número com número, não string com número.
    if ((int) $consulta['patient_id'] !== $pacienteId) {
        throw new RuntimeException('Você não tem permissão para cancelar essa consulta.');
    }

    // Não faz sentido cancelar uma consulta que já foi cancelada antes, ou que já
    // aconteceu (status 'completed'). Só deixamos cancelar quem ainda está
    // 'pending' (aguardando confirmação) ou 'confirmed' (confirmada).
    if (!in_array($consulta['status'], ['pending', 'confirmed'], true)) {
        throw new RuntimeException('Essa consulta não pode mais ser cancelada.');
    }

    // Monta dois "relógios": um com a hora de agora, outro com a hora que a
    // consulta está marcada pra começar. DateTime é uma classe pronta do PHP pra
    // trabalhar com datas sem a gente ter que fazer conta de calendário na mão
    // (quantos dias tem cada mês, ano bissexto, etc. Ela já sabe disso tudo).
    $agora = new DateTime();
    $inicioConsulta = new DateTime($consulta['slot_start']);

    // getTimestamp() transforma a data em um número: quantos segundos se passaram
    // desde 01/01/1970 até aquele momento (é assim que o computador "entende" datas
    // por baixo dos panos). Subtraindo um do outro, descobrimos quantos segundos
    // faltam até a consulta; dividindo por 3600 (segundos numa hora), isso vira horas.
    $horasAteConsulta = ($inicioConsulta->getTimestamp() - $agora->getTimestamp()) / 3600;

    // Regra da clínica: só pode cancelar com pelo menos 24h de antecedência. Se
    // faltar menos que isso (ou se a consulta já passou, o que dá um número
    // negativo aqui), barra o cancelamento.
    if ($horasAteConsulta < 24) {
        throw new RuntimeException('Só é possível cancelar com pelo menos 24 horas de antecedência.');
    }

    // Chegou até aqui? Então pode cancelar de verdade. Precisamos mudar DUAS
    // tabelas: marcar a consulta como cancelada E liberar o horário (slot) pra
    // outro paciente poder agendar nele. Usamos uma transação pra garantir que as
    // duas mudanças aconteçam juntas, se uma der certo e a outra falhar (o banco
    // cair no meio do caminho, por exemplo), desfazemos tudo, em vez de deixar o
    // banco "pela metade" (consulta cancelada, mas horário continua preso).
    $pdo = db();

    try {
        // beginTransaction() é como dizer "a partir de agora, guarda tudo em
        // rascunho, não grava de verdade ainda até eu mandar confirmar"
        $pdo->beginTransaction();

        // muda o status da consulta pra 'cancelled'
        $stmt = $pdo->prepare('UPDATE appointments SET status = ? WHERE id = ?');
        $stmt->execute(['cancelled', $consultaId]);

        // libera o horário de volta, pra aparecer como disponível pra outros
        // pacientes que forem agendar depois
        $stmt = $pdo->prepare('UPDATE appointment_slots SET status = ? WHERE id = ?');
        $stmt->execute(['available', $consulta['slot_id']]);

        // avisa o paciente que o cancelamento foi registrado
        notificar_paciente($pacienteId, $consultaId, 'Consulta cancelada', 'Sua consulta foi cancelada.');

        // as duas mudanças deram certo, então agora manda gravar de verdade no
        // banco, "assina embaixo do rascunho"
        $pdo->commit();
    } catch (Exception $e) {
        // se QUALQUER uma das duas linhas acima falhar, cai aqui: desfaz
        // qualquer mudança de rascunho que tenha sido feita (rollBack = "joga
        // fora o rascunho, volta tudo como estava antes de começar")
        $pdo->rollBack();

        // relança um erro (com uma mensagem amigável) pra quem chamou essa
        // função saber que algo deu errado, em vez de fingir que cancelou
        throw new RuntimeException('Não foi possível cancelar a consulta. Tente novamente.');
    }
}

// AGENDAR

// CLÍNICAS (tela 1 do fluxo de Agendar)

// Lista as clínicas que têm PELO MENOS 1 médico ativo atendendo nelas, já
// trazendo quantos médicos ativos cada uma tem, e também os NOMES desses
// médicos e das especialidades deles, usamos isso pra montar a busca e o
// filtro por especialidade na tela, sem precisar consultar o banco de novo
// a cada letra digitada. $busca é opcional: filtra pelo NOME da clínica.
function active_clinics_with_doctor_count(?string $busca = null): array
{
    $sql = "
        SELECT clin.id AS clinic_id, clin.name AS clinic_name,
               clin.address AS clinic_address, clin.phone AS clinic_phone,
               COUNT(doc.id) AS total_medicos,
               GROUP_CONCAT(DISTINCT medico.name SEPARATOR ', ') AS nomes_medicos,
               GROUP_CONCAT(DISTINCT esp.name SEPARATOR ', ') AS nomes_especialidades,
               EXISTS (
                   SELECT 1
                   FROM appointment_slots vaga
                   JOIN doctors doc_vaga ON doc_vaga.id = vaga.doctor_id
                   WHERE doc_vaga.clinic_id = clin.id
                     AND doc_vaga.active = 1
                     AND vaga.status = 'available'
                     AND vaga.slot_start >= NOW()
               ) AS tem_horario_disponivel
        FROM clinics clin
        JOIN doctors doc ON doc.clinic_id = clin.id AND doc.active = 1
        JOIN users medico ON medico.id = doc.user_id
        JOIN specialties esp ON esp.id = doc.specialty_id
    ";
    // EXISTS é tipo uma pergunta de sim/não pro banco: 'existe
    // pelo menos 1 linha que bate com isso aqui?'. Aqui a
    // pergunta é: 'essa clínica tem algum horário com status
    // available, no futuro, de algum médico ativo dela?'

    // GROUP_CONCAT é uma função do MySQL que faz o seguinte: quando várias
    // linhas são "amassadas" numa só por causa do GROUP BY (lá embaixo),
    // ela pega o valor de CADA linha que seria perdida no processo e junta
    // tudo numa string só, separada pelo texto que a gente escolher (aqui,
    // ", "). Sem isso, ao juntar os médicos de 3 especialidades diferentes
    // numa clínica só numa linha de resultado, a gente só conseguiria ver
    // o nome de UM médico, os outros "sumiriam". O "DISTINCT" aí dentro
    // evita repetir a mesma especialidade duas vezes, caso 2 médicos
    // daquela clínica sejam da mesma área

    $params = [];

    if ($busca !== null && trim($busca) !== '') {
        $sql .= ' WHERE clin.name LIKE ?';
        $params[] = '%' . trim($busca) . '%';
    }

    $sql .= ' GROUP BY clin.id ORDER BY clin.name ASC';

    $stmt = db()->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

// Lista as especialidades que têm PELO MENOS 1 médico ativo em qualquer
// clínica, usada pra montar as opções do filtro "Especialidade" na tela
// de Agendar (não faz sentido oferecer um filtro por uma especialidade que
// nenhum médico ativo atende no momento)
function active_specialties(): array
{
    $sql = "
        SELECT DISTINCT esp.id AS specialty_id, esp.name AS specialty_name
        FROM specialties esp
        JOIN doctors doc ON doc.specialty_id = esp.id AND doc.active = 1
        ORDER BY esp.name ASC
    ";
    $stmt = db()->prepare($sql);
    $stmt->execute();
    return $stmt->fetchAll();
}

// Busca os dados de UMA clínica específica pelo id (usada no topo da tela 2,
// pra mostrar nome/endereço da clínica que a pessoa escolheu)
function find_clinic_details(int $clinicaId): ?array
{
    $stmt = db()->prepare('
        SELECT id AS clinic_id, name AS clinic_name, address AS clinic_address, phone AS clinic_phone
        FROM clinics
        WHERE id = ?
    ');
    $stmt->execute([$clinicaId]);
    $linha = $stmt->fetch();
    return $linha ?: null;
}

// MÉDICOS (tela 2 do fluxo de Agendar: médicos DE UMA clínica específica)
// $clinicaId agora é OBRIGATÓRIO: precisamos saber de qual clínica queremos
// os médicos. $busca é opcional, filtra pelo nome do médico dentro dela.
function active_doctors_with_details(int $clinicaId, ?string $busca = null): array
{
    $sql = "
        SELECT doc.id AS doctor_id, medico.name AS doctor_name,
               esp.name AS specialty_name, clin.name AS clinic_name,
               doc.crm AS doctor_crm
        FROM doctors doc
        JOIN users medico ON medico.id = doc.user_id
        JOIN specialties esp ON esp.id = doc.specialty_id
        JOIN clinics clin ON clin.id = doc.clinic_id
        WHERE doc.active = 1 AND doc.clinic_id = ?
    ";
    $params = [$clinicaId];

    if ($busca !== null && trim($busca) !== '') {
        $sql .= ' AND medico.name LIKE ?';
        $params[] = '%' . trim($busca) . '%';
    }

    $sql .= ' ORDER BY medico.name ASC';

    $stmt = db()->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

// a mesma busca de um médico específico de sempre, só que agora também
// trazendo o clinic_id junto (não só o nome da clínica), porque a tela de
// horários vai precisar dele pra montar o link de "voltar pra lista de
// médicos dessa clínica"
function find_doctor_details(int $medicoId): ?array
{
    $sql = "
        SELECT doc.id AS doctor_id, medico.name AS doctor_name,
               esp.name AS specialty_name, clin.name AS clinic_name,
               clin.id AS clinic_id
        FROM doctors doc
        JOIN users medico ON medico.id = doc.user_id
        JOIN specialties esp ON esp.id = doc.specialty_id
        JOIN clinics clin ON clin.id = doc.clinic_id
        WHERE doc.id = ? AND doc.active = 1
    ";
    $stmt = db()->prepare($sql);
    $stmt->execute([$medicoId]);
    $linha = $stmt->fetch();
    return $linha ?: null;
}

/**
 * Garante que, entre $fromDate e $toDate, todo dia da semana em que esse
 * médico atende (cadastro em doctor_schedules, a "Atendimento semanal" do
 * painel do médico/admin) já tenha um horário de verdade criado na tabela
 * appointment_slots. Sem essa função, um dia novo cadastrado no
 * Atendimento semanal só virava horário visível depois que alguém abria
 * aquele dia específico lá no painel do médico/admin (é lá que essa
 * mesma "criação sob demanda" já acontecia), aqui replicamos a mesma
 * ideia, agora do lado do paciente também.
 */
function ensure_slots_for_doctor(int $doctorId, string $fromDate, string $toDate): void
{
    $medico = repository_find('doctors', $doctorId);
    if (!$medico || !(int) ($medico['active'] ?? 0)) {
        // médico não existe ou está inativo: não faz sentido gerar
        // horário nenhum pra ele
        return;
    }

    $limiteInicial = $fromDate . ' 00:00:00';
    $limiteFinal = (new DateTime($toDate))->modify('+1 day')->format('Y-m-d') . ' 00:00:00';
    // guardamos essas duas "pontas" do período aqui em cima porque vamos
    // usar as duas tanto na limpeza quanto na geração, mais abaixo

    // ANTES de gerar qualquer coisa nova, apaga os horários 'available'
    // (livres) e 'blocked' (bloqueados) desse médico dentro do período,
    // ou seja, os que ninguém marcou ainda. É isso que faz o calendário
    // se "auto-corrigir" quando você muda a duração da consulta ou apaga
    // um dia do Atendimento semanal: sem essa limpeza, os horários
    // antigos (nos 30 minutos de antes, ou de um dia que você já tirou
    // da grade) ficavam presos no banco pra sempre, porque essa função só
    // sabia criar horário novo, nunca sabia apagar o que tinha ficado
    // desatualizado. Um horário 'booked' (com consulta marcada de
    // verdade) NUNCA é apagado aqui, só os que ainda estão livres ou
    // bloqueados e SEM nenhuma consulta de verdade grudada neles (é o que
    // o "NOT EXISTS" confere)
    $stmtLimpeza = db()->prepare(
        "DELETE FROM appointment_slots
         WHERE doctor_id = ?
           AND slot_start >= ?
           AND slot_start < ?
           AND status IN ('available', 'blocked')
           AND NOT EXISTS (
               SELECT 1 FROM appointments ap WHERE ap.slot_id = appointment_slots.id
           )"
    );
    $stmtLimpeza->execute([$doctorId, $limiteInicial, $limiteFinal]);

    // busca a grade semanal recorrente desse médico (ex.: "toda quarta,
    // das 08h às 12h"), é essa tabela que o painel do médico/admin chama
    // de "Atendimento semanal"
    $stmtGrade = db()->prepare('SELECT * FROM doctor_schedules WHERE doctor_id = ? AND active = 1');
    $stmtGrade->execute([$doctorId]);
    $grades = $stmtGrade->fetchAll();

    // busca os bloqueios pontuais desse médico dentro do período (férias,
    // folga...), pra já marcar como 'blocked' os horários que caem em
    // cima de um bloqueio, em vez de deixar como 'available'
    $stmtBloqueios = db()->prepare('SELECT * FROM schedule_blocks WHERE doctor_id = ? AND block_date >= ? AND block_date <= ?');
    $stmtBloqueios->execute([$doctorId, $fromDate, $toDate]);
    $bloqueios = $stmtBloqueios->fetchAll();

    // pega todos os slot_start que esse médico já tem no banco, pra nunca
    // tentar criar um horário duplicado (a tabela tem uma trava UNIQUE em
    // (doctor_id, slot_start) que barraria isso de qualquer jeito, mas é
    // mais barato já conferir aqui do que deixar o banco recusar)
    $stmtExistentes = db()->prepare('SELECT slot_start FROM appointment_slots WHERE doctor_id = ?');
    $stmtExistentes->execute([$doctorId]);
    $existentes = array_fill_keys(array_column($stmtExistentes->fetchAll(), 'slot_start'), true);

    $duracao = max(10, (int) ($medico['appointment_duration'] ?? 30));
    // cada consulta dura esse tanto de minutos (30 é o valor padrão, caso
    // o médico não tenha essa coluna preenchida)

    $dataAtual = new DateTime($fromDate);
    // reaproveitandoa variavel $limiteFinal, que ja calculamos acima, sem 
    // precisar recalcular aqui de novo.
    $dataFinal = new DateTime($limiteFinal);
    $novosSlots = [];

    while ($dataAtual < $dataFinal) {
        $diaDaSemana = (int) $dataAtual->format('w');
        // 'w' devolve 0 (domingo) até 6 (sábado), a mesma convenção usada
        // na coluna doctor_schedules.weekday

        foreach ($grades as $grade) {
            if ((int) $grade['weekday'] !== $diaDaSemana) {
                continue;
                // essa regra de horário não é desse dia da semana, pula
                // pra próxima regra
            }

            $inicioSlot = new DateTime($dataAtual->format('Y-m-d') . ' ' . $grade['start_time']);
            $limite = new DateTime($dataAtual->format('Y-m-d') . ' ' . $grade['end_time']);

            while ($inicioSlot < $limite) {
                $fimSlot = (clone $inicioSlot)->modify('+' . $duracao . ' minutes');
                if ($fimSlot > $limite) {
                    break;
                    // não sobra tempo suficiente pra mais uma consulta
                    // inteira antes do fim do expediente, para por aqui
                }

                $inicio = $inicioSlot->format('Y-m-d H:i:s');

                if (!isset($existentes[$inicio])) {
                    // confere se esse horário cai em cima de algum
                    // bloqueio (férias, folga...), pra já nascer 'blocked'
                    $bloqueioEncontrado = null;
                    foreach ($bloqueios as $bloqueio) {
                        $inicioBloqueio = new DateTime($bloqueio['block_date'] . ' ' . $bloqueio['start_time']);
                        $fimBloqueio = new DateTime($bloqueio['block_date'] . ' ' . $bloqueio['end_time']);
                        if ($inicioSlot < $fimBloqueio && $fimSlot > $inicioBloqueio) {
                            $bloqueioEncontrado = $bloqueio;
                            break;
                        }
                    }

                    $novosSlots[] = [
                        'doctor_id' => $doctorId,
                        'slot_start' => $inicio,
                        'slot_end' => $fimSlot->format('Y-m-d H:i:s'),
                        'status' => $bloqueioEncontrado ? 'blocked' : 'available',
                        'block_reason' => $bloqueioEncontrado['reason'] ?? null,
                    ];
                    $existentes[$inicio] = true;
                    // já marca como "existente" aqui mesmo, pra não montar
                    // esse mesmo horário de novo se duas regras se
                    // sobrepusessem por engano
                }

                $inicioSlot = $fimSlot;
            }
        }

        $dataAtual->modify('+1 day');
    }

    foreach ($novosSlots as $slot) {
        repository_append('appointment_slots', $slot);
        // reaproveita a função de INSERT genérica que já existe aqui em
        // cima nesse mesmo arquivo, uma chamada por horário novo
    }
}

function available_slots_for_doctor(int $medicoId): array
{
    // antes de ler o que já existe no banco, garante que os próximos 90
    // dias de horários (baseados na grade semanal cadastrada em
    // doctor_schedules) já estão criados como linhas de verdade em
    // appointment_slots. Sem essa chamada, um dia novo cadastrado no
    // "Atendimento semanal" só apareceria pro paciente depois que alguém
    // abrisse aquele dia específico no painel do médico/admin
    ensure_slots_for_doctor($medicoId, date('Y-m-d'), date('Y-m-d', strtotime('+90 days')));

    // busca os horários livres desse médico, só os que ainda vão acontecer
    // (slot_start >= NOW(), NOW() é uma função do próprio MySQL que pega a
    // data/hora atual do servidor do banco), ordenados do mais próximo pro
    // mais distante
    $sql = "
        SELECT id, slot_start, slot_end
        FROM appointment_slots
        WHERE doctor_id = ? AND status = 'available' AND slot_start >= NOW()
        ORDER BY slot_start ASC
    ";
    $stmt = db()->prepare($sql);
    $stmt->execute([$medicoId]);
    return $stmt->fetchAll();
}

// BUSCA (usada na Home): procura ao mesmo tempo por médicos e por clínicas
// cujo nome bata com o texto digitado, e devolve os dois resultados juntos,
// separados em duas "gavetas" do mesmo array de resposta.
function search_doctors_and_clinics(string $busca): array
{
    $termo = '%' . trim($busca) . '%';
    // monta o "parecido com" uma única vez aqui em cima, já que vamos usar
    // esse mesmo texto em mais de uma consulta abaixo

    // médicos: procura tanto pelo NOME do médico quanto pelo nome da
    // ESPECIALIDADE dele (assim, buscar "cardiologista" também encontra os
    // médicos dessa especialidade, não só alguém que se chame isso)
    $stmtMedicos = db()->prepare("
        SELECT doc.id AS doctor_id, medico.name AS doctor_name,
               esp.name AS specialty_name, clin.name AS clinic_name,
               clin.id AS clinic_id, doc.crm AS doctor_crm
        FROM doctors doc
        JOIN users medico ON medico.id = doc.user_id
        JOIN specialties esp ON esp.id = doc.specialty_id
        JOIN clinics clin ON clin.id = doc.clinic_id
        WHERE doc.active = 1
          AND (medico.name LIKE ? OR esp.name LIKE ?)
        ORDER BY medico.name ASC
    ");
    $stmtMedicos->execute([$termo, $termo]);
    // repara que mandamos o MESMO $termo duas vezes: um "?" pra cada lado
    // do "OR" ali em cima (nome do médico OU nome da especialidade)

    // clínicas: procura só pelo nome da própria clínica, e também só
    // considera clínicas com pelo menos 1 médico ativo (mesma regra da
    // lista principal), pra nunca sugerir uma clínica "vazia"
    $stmtClinicas = db()->prepare("
        SELECT clin.id AS clinic_id, clin.name AS clinic_name,
               clin.address AS clinic_address
        FROM clinics clin
        JOIN doctors doc ON doc.clinic_id = clin.id AND doc.active = 1
        WHERE clin.name LIKE ?
        GROUP BY clin.id
        ORDER BY clin.name ASC
    ");
    $stmtClinicas->execute([$termo]);

    return [
        'medicos' => $stmtMedicos->fetchAll(),
        'clinicas' => $stmtClinicas->fetchAll(),
    ];
    // devolve um array com DUAS gavetas: quem chamar essa função escreve
    // $resultado['medicos'] e $resultado['clinicas'] pra pegar cada lista
}

function book_appointment_patient(int $pacienteId, int $slotId): void
{
    $pdo = db();

    try {
        $pdo->beginTransaction();

        // trava essa linha do horário, mesma ideia de antes: enquanto essa
        // transação não terminar, mais ninguém consegue mexer nesse mesmo
        // horário ao mesmo tempo
        $stmt = $pdo->prepare('
            SELECT id, doctor_id, status
            FROM appointment_slots
            WHERE id = ?
            FOR UPDATE
        ');
        $stmt->execute([$slotId]);
        $slot = $stmt->fetch();

        if (!$slot) {
            throw new RuntimeException('Horário não encontrado.');
        }

        if ($slot['status'] !== 'available') {
            throw new RuntimeException('Esse horário não está mais disponível. Escolha outro.');
        }

        $stmtMedico = $pdo->prepare('SELECT clinic_id, specialty_id FROM doctors WHERE id = ?');
        $stmtMedico->execute([$slot['doctor_id']]);
        $medico = $stmtMedico->fetch();

        if (!$medico) {
            throw new RuntimeException('Médico não encontrado.');
        }

        // A tabela "appointments" só permite UMA linha por horário, pra
        // sempre (é a trava que causou o erro anteriormente). Então, antes de
        // criar uma consulta nova, a gente pergunta: já existe uma linha
        // (provavelmente de uma consulta cancelada antes) usando esse mesmo
        // horário? FOR UPDATE aqui também trava essa possível linha antiga,
        // pelo mesmo motivo de segurança de sempre.
        $stmtExistente = $pdo->prepare('SELECT id FROM appointments WHERE slot_id = ? FOR UPDATE');
        $stmtExistente->execute([$slotId]);
        $consultaAntiga = $stmtExistente->fetch();

        if ($consultaAntiga) {
            // já existe uma linha antiga pra esse horário (uma consulta
            // cancelada, provavelmente): em vez de tentar inserir uma linha
            // NOVA (o que a trava do banco recusaria, causando aquele erro),
            // a gente REAPROVEITA essa linha, atualizando ela com os dados
            // da nova consulta
            $stmtUpdate = $pdo->prepare('
                UPDATE appointments
                SET patient_id = ?, doctor_id = ?, clinic_id = ?, specialty_id = ?,
                    status = ?, modality = ?
                WHERE id = ?
            ');
            $stmtUpdate->execute([
                $pacienteId,
                $slot['doctor_id'],
                $medico['clinic_id'],
                $medico['specialty_id'],
                'confirmed',
                'presencial',
                $consultaAntiga['id'],
            ]);

            $appointmentId = (int) $consultaAntiga['id'];
            // guardamos o id aqui porque vamos precisar dele daqui a
            // pouco, pra criar a notificação vinculada a ESSA consulta
        } else {
            // primeira vez que alguém agenda nesse horário: aí sim cria a
            // linha nova, do jeito que já era antes
            $stmtInsert = $pdo->prepare('
                INSERT INTO appointments (slot_id, patient_id, doctor_id, clinic_id, specialty_id, status, modality)
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ');
            $stmtInsert->execute([
                $slotId,
                $pacienteId,
                $slot['doctor_id'],
                $medico['clinic_id'],
                $medico['specialty_id'],
                'confirmed',
                'presencial',
            ]);

            $appointmentId = (int) $pdo->lastInsertId();
            // esse caminho é o de uma consulta NOVA (não reaproveitando
            // uma cancelada antes), então o id vem do INSERT que acabou
            // de rodar
        }

        // marca o horário como ocupado, igual já era
        $stmtOcupa = $pdo->prepare('UPDATE appointment_slots SET status = ? WHERE id = ?');
        $stmtOcupa->execute(['booked', $slotId]);

         // avisa o paciente que a consulta foi confirmada. O agendamento
        // não passa por uma etapa separada de aprovação (o MedAdmin
        // inclusive já desativou essa confirmação manual), então a
        // consulta nasce direto como 'confirmed', e a notificação reflete
        // isso. Não precisamos escrever nome de médico/data na mensagem:
        // a tela de Notificações já busca isso sozinha através do
        // appointment_id, sempre que a notificação tiver um vinculado
        notificar_paciente($pacienteId, $appointmentId, 'Consulta confirmada', 'Sua consulta foi confirmada.');

        $pdo->commit();
    } catch (Exception $e) {
        $pdo->rollBack();

        if ($e instanceof RuntimeException) {
            throw $e;
        }

        throw new RuntimeException('Não foi possível marcar a consulta. Tente novamente.');
    }
}

function update_patient_profile(int $pacienteId, array $dados): void
{
    // trim() tira espaços em branco do início/fim (tipo se a pessoa digitou
    // " Maria " sem querer, com espaço sobrando). (string) garante que, mesmo
    // se a chave não vier no array, viramos uma string vazia em vez de dar erro
    $nome = trim((string) ($dados['name'] ?? ''));
    $email = trim((string) ($dados['email'] ?? ''));
    $telefone = trim((string) ($dados['phone'] ?? ''));
    $documento = trim((string) ($dados['document'] ?? ''));
    $dataNascimento = trim((string) ($dados['birth_date'] ?? ''));
    $endereco = trim((string) ($dados['address'] ?? ''));

    if ($nome === '') {
        throw new RuntimeException('Informe seu nome.');
    }

    // filter_var(..., FILTER_VALIDATE_EMAIL) é uma função pronta do PHP pra
    // conferir se um texto tem cara de e-mail (tem "@", tem domínio depois...).
    // Se o texto não passar nesse formato, ela devolve "false"
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        throw new RuntimeException('Informe um e-mail válido.');
    }

    // confere se já existe outra conta usando esse e-mail (a coluna email é
    // UNIQUE no banco, então se a gente nem checar isso, o UPDATE lá embaixo
    // ia estourar um erro feio do MySQL em vez de uma mensagem amigável)
    $usuarioComEsseEmail = find_user_by_email($email);

    // se achou alguém com esse e-mail, mas o id dessa pessoa é DIFERENTE do
    // id de quem está editando o próprio perfil, é porque o e-mail já é de
    // outra conta (se for o mesmo id, tudo bem, é a pessoa mantendo o
    // próprio e-mail sem mudar nada)
    if ($usuarioComEsseEmail && (int) $usuarioComEsseEmail['id'] !== $pacienteId) {
        throw new RuntimeException('Esse e-mail já está sendo usado por outra conta.');
    }

    $sql = "
        UPDATE users
        SET name = ?, email = ?, phone = ?, document = ?, birth_date = ?, address = ?
        WHERE id = ?
    ";
    $stmt = db()->prepare($sql);
    $stmt->execute([
        $nome,
        $email,
        // os campos opcionais: se a pessoa deixou em branco, guardamos NULL
        // no banco em vez de uma string vazia "", fica mais limpo (diz "não
        // preenchido" em vez de "preenchido com nada")
        $telefone !== '' ? $telefone : null,
        $documento !== '' ? $documento : null,
        $dataNascimento !== '' ? $dataNascimento : null,
        $endereco !== '' ? $endereco : null,
        $pacienteId,
    ]);
}

// TERMOS DE USO
function accept_terms(int $userId): void
{
    // reaproveita o repository_update_user() que já existe (o mesmo usado
    // em outros lugares do sistema), ele já cuida de atualizar o
    // updated_at sozinho, então só precisamos dizer QUAL coluna muda
    repository_update_user($userId, ['terms_accepted' => 1]);
}

// TUTORIAL DE PRIMEIRO ACESSO
function mark_tutorial_seen(int $userId): void
{
    repository_update_user($userId, ['tutorial_seen' => 1]);
}

// NOTIFICAÇÕES
function notifications_for_patient(int $pacienteId): array
{
    // busca os avisos desse paciente que já foram enviados (status 'sent'(já enviado))
    // ou que a pessoa já tinha visto antes ('read' (a pessoa já viu)), do mais recente pro
    // mais antigo
     $sql = "
        SELECT
            n.id,
            n.title,
            n.message,
            n.status,
            n.sent_at,
            medico.name AS doctor_name,
            clin.name AS clinic_name,
            slot.slot_start
        FROM notifications n
        LEFT JOIN appointments a ON a.id = n.appointment_id
        LEFT JOIN doctors doc ON doc.id = a.doctor_id
        LEFT JOIN users medico ON medico.id = doc.user_id
        LEFT JOIN clinics clin ON clin.id = a.clinic_id
        LEFT JOIN appointment_slots slot ON slot.id = a.slot_id
        WHERE n.user_id = ? AND n.status IN ('sent', 'read')
        ORDER BY n.sent_at DESC
    ";
    $stmt = db()->prepare($sql);
    $stmt->execute([$pacienteId]);
    return $stmt->fetchAll();
}

function mark_notifications_as_read(int $pacienteId): void
{
    // marca como "lidas" todas as notificações desse paciente que ainda
    // estavam como 'sent' (ou seja: enviadas, mas a pessoa ainda não tinha
    // entrado nessa tela pra ver). NOW() pega a data/hora atual do MySQL,
    // registrando exatamente quando ela "leu"
    $sql = "UPDATE notifications SET status = 'read', read_at = NOW() WHERE user_id = ? AND status = 'sent'";
    $stmt = db()->prepare($sql);
    $stmt->execute([$pacienteId]);
}

// Atalho pra criar uma notificação nova, sem repetir o mesmo array de 9
// campos toda vez, usada ao marcar consulta, ao cancelar, e também no
// lembrete automático logo abaixo
function notificar_paciente(int $pacienteId, ?int $appointmentId, string $titulo, string $mensagem): void
{
    repository_append('notifications', [
        'user_id' => $pacienteId,
        'appointment_id' => $appointmentId,
        'type' => 'in_app',
        'title' => $titulo,
        'message' => $mensagem,
        'status' => 'sent',
        'send_at' => now_sql(),
        'sent_at' => now_sql(),
        'read_at' => null,
        'created_at' => now_sql(),
    ]);
}

// conta quantas notificações desse paciente ainda estão como 'sent'
// (enviadas, mas ele ainda não abriu a tela de Notificações pra ver).
// Usada pra desenhar o sininho/contador no menu, em render_header()
function unread_notifications_count(int $pacienteId): int
{
    $stmt = db()->prepare("SELECT COUNT(*) FROM notifications WHERE user_id = ? AND status = 'sent'");
    $stmt->execute([$pacienteId]);
    return (int) $stmt->fetchColumn();
}