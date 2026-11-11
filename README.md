# Pousada Costa Serena — Versão 3 (Laravel + MySQL + ServBay)

Esta entrega contém o projeto Laravel com o **site público renovado**, **painel administrativo em estilo BI**, **Páginas do site com criação, edição, publicação e pré-visualização**, **biblioteca de fotografias ilustrativas** e **calendário interativo local**. O código de reservas, hospedagem, financeiro, acomodações, funcionários e governança continua incluído.

> **Atenção:** o ZIP não inclui pacotes de terceiros em `vendor/` nem `node_modules/`. Para instalação nova, execute o Composer 2 uma vez. Não há como validar seu Windows/ServBay/MySQL a partir desta entrega; consulte `VALIDACAO.md`. Não coloque dados de hóspedes reais antes de homologar os fluxos e a segurança.

## 1. Se você já está usando a pousada no ServBay (recomendado)

1. **Faça backup** do diretório Laravel e do banco MySQL antes de atualizar.
2. Extraia **todo** este ZIP em uma pasta separada, fora da instalação atual (por exemplo, na pasta Downloads).
3. Execute **`APLICAR-VERSAO-3.bat`** dentro da pasta extraída `pousada-costa-serena`.
4. Informe o caminho da **raiz da instalação atual** (pasta que possui `artisan` e `composer.json`), e não a pasta `public`.
5. Confirme a cópia integral de controllers, views, CSS/JS e rotas. Os originais sobrescritos serão salvos em `storage/backups/costa-v3-<data>` da instalação atual.
6. O script preserva `vendor`, `.env`, `storage`, banco MySQL, reservas e usuários; não executa migrations ou reset de dados. Após a cópia, execute `php artisan optimize:clear` se necessário.
7. Abra `https://pousada.test`, `/admin`, `/admin/paginas`, `/admin/calendario` e `/admin/midias`, e pressione `Ctrl+F5`.

**Não copie apenas uma view Blade sem o controller correspondente.** Os erros anteriores `Undefined variable $canReservations` e `Undefined variable $publishedCount` surgiram de versões desencontradas de arquivo.

## 2. Instalação nova no Windows/ServBay

Pré-requisitos: Windows, Apache do ServBay ativo, PHP **8.3+** com `pdo_mysql`, `mbstring`, `fileinfo`, `openssl`, Composer 2, MySQL 8 (e internet para instalar as dependências Laravel na primeira execução).

1. Extraia o ZIP para uma pasta como `C:\ServBay\www\pousada-costa-serena`, ou outro diretório escolhido. O ServBay não depende de um caminho fixo.
2. No ServBay, configure **Apache** e um site com domínio `pousada.test`; o **DocumentRoot deve ser a pasta `public`** do projeto, nunca a raiz.
3. Execute `install.bat` e siga o terminal, mantendo a janela aberta até a confirmação. O instalador prepara `.env`, solicita os dados MySQL, executa `composer install`, cria a chave da aplicação se estiver ausente, aplica migrations e **pergunta antes de popular** o banco com dados fictícios.
4. Caso tenha extraído o projeto mas não tenha executado o Composer, execute `INSTALAR-DEPENDENCIAS.bat` (ou `composer install` na pasta raiz). `vendor/autoload.php` só existe após essa etapa.
5. Acesse `http://pousada.test` ou `https://pousada.test` se houver certificado local confiável. Caso use HTTPS, ajuste `APP_URL` e `SESSION_SECURE_COOKIE=true` no `.env` e execute `php artisan optimize:clear`.
6. Execute `VERIFICAR-AMBIENTE.bat` para checar extensões, autoload, `.env` e recursos locais. Ele **não testa** banco ou navegador.

### Comandos manuais úteis

```powershell
cd C:\ServBay\www\pousada-costa-serena
composer install
php artisan key:generate
php artisan migrate:status
php artisan migrate
php artisan db:seed
php artisan optimize:clear
php artisan route:list
```

**Atenção:** `db:seed` instala contas/dados fictícios de desenvolvimento. Em bancos existentes, faça backup primeiro. **NUNCA** execute `migrate:fresh`, `reset-demo.bat` ou `db:wipe` sobre dados que deseja preservar.

## 3. Contas demonstrativas (somente ambiente local)

| Perfil | E-mail | Senha |
|---|---|---|
| Administrador | admin@costaserena.test | Admin@12345 |
| Recepcionista | recepcao@costaserena.test | Recep@12345 |
| Financeiro | financeiro@costaserena.test | Finan@12345 |
| Governança | governanca@costaserena.test | Gover@12345 |
| Hóspede | hospede@costaserena.test | Hosp@12345 |

As senhas são armazenadas com hash. Não utilize essas contas na internet nem em produção.

## 4. Funcionalidades da versão 3

- **Site público:** layout costeiro verde/areia/dourado inspirado na referência fornecida, hero fotográfico, busca de disponibilidade, acomodações, experiências, galeria e páginas institucionais. Fotografias em `public/assets/photos/` são geradas e **ilustrativas**, não representam pousada real.
- **Dashboard:** indicadores e gráficos calculados no MySQL, filtros de período, exportação CSV, acesso rápido, status das reservas, agenda de check-ins e tabela de reservas. Informações financeiras dependem da permissão do usuário.
- **Páginas:** edição e publicação, novo cadastro de páginas, busca, pré-visualização segura de texto, opção salvar rascunho ou publicar. Novas páginas são acessíveis em `/pagina/<slug>`; não aparecem automaticamente no menu principal.
- **Mídias:** escolha da foto principal via `/admin/midias` persistida na tabela `settings`, sem migrations adicionais.
- **Calendário:** consulta de eventos via `/admin/calendario/eventos`, navegação mensal, filtro por quarto e detalhe dos dias. Funciona com JavaScript local, sem CDN ou npm obrigatório. Nomes de hóspedes só aparecem a quem possui a permissão apropriada.
- **Relatórios:** barras de receita/despesas geradas com HTML/CSS local, sem depender de Chart.js para visualização.

## 5. Testes e limites

O projeto inclui testes PHPUnit em `tests/Feature` e `tests/Unit`. Eles **não foram executados** no ambiente de geração porque aqui faltam Composer, MySQL e `pdo_mysql`. O `phpunit.xml` exige um **banco separado** chamado `pousada_costa_serena_test`; crie esse banco de teste com credenciais autorizadas antes de rodar `php artisan test`. O uso de `RefreshDatabase` apaga dados do banco **de teste**; não aponte testes para o banco real da pousada.

A verificação estática (lint PHP, JavaScript, balanceamento de diretivas Blade, imagens e ZIP) está documentada em `VALIDACAO.md`. Ela não substitui os testes de integração no Windows. A especificação integral original tem funcionalidades ainda pendentes, descritas em `STATUS.md`.

## 6. Resolução de erros comuns

- **`vendor/autoload.php` ausente:** execute `INSTALAR-DEPENDENCIAS.bat` na raiz, com Composer 2 e internet.
- **`Undefined variable` em views:** substitua **controladores e templates da mesma versão**, usando `APLICAR-VERSAO-3.bat`, e limpe o cache. Não atualize apenas arquivos Blade.
- **Erro SQL/MySQL:** confirme serviço ativo no ServBay, banco e usuário no `.env`, PHP correto e extensão `pdo_mysql` habilitada.
- **404 em URLs amigáveis:** configure DocumentRoot para `public`, habilite `mod_rewrite` do Apache e permita o uso de `.htaccess`.
- **CSS antigo:** `php artisan optimize:clear` e `Ctrl+F5`. Caso tenha `public/build/manifest.json`, a aplicação usa os assets Vite e inclui os novos CSS/JS de forma complementar.
- **Erro de upload de quarto:** confira o link `public/storage` após `php artisan storage:link` e as permissões do diretório `storage/app/public`.

## 7. Observações de segurança

As fotografias e dados são fictícios. O projeto não está homologado para produção. É necessário revisar LGPD, integrações financeiras, restrições de reserva concorrente, acesso/privacidade, autenticação, rotinas de backup e testes end-to-end com seu banco e servidor antes de expor o site ao público.
