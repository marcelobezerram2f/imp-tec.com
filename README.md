# IMPTEC — HTML, CSS, JavaScript e PHP

Conversão do ZIP original enviado. Sem .NET, Docker, Node ou build no servidor. Conteúdo das seções escrito no HTML. Apenas o envio de e-mail utiliza PHP 8.2+ e PHPMailer.

## Estrutura

- `public/index.html`: todos os textos, serviços, links e depoimentos.
- `public/assets/css/site.css`: CSS original preservado.
- `public/assets/css/custom.css`: ajustes do logo, acessibilidade e estados do formulário.
- `public/assets/js/site.js`: JavaScript original preservado.
- `public/assets/js/landing.js`: menu móvel, carrossel e formulário.
- `public/assets/images/logo-imptec.png`: logo do ZIP original, baixado da referência externa. Substitua pelo logo local usado atualmente no servidor, preferencialmente recortado sem margens.
- `public/api/contato.php`: endpoint de envio SMTP.
- `config/email.example.php`: modelo de configuração; copie para `email.php`.
- `composer.json`: dependência PHPMailer (instalação única).
- `deploy/cloudpanel-vhost.conf`: VHOST para HTML e PHP-FPM.

As alterações posteriores feitas no servidor não constam do ZIP de origem. Foram preservados os textos, métricas, depoimentos, WhatsApp e contatos desse ZIP; revise esses conteúdos antes de publicar. Os links sociais e institucionais `#` do original ainda são placeholders. A frase do formulário foi ajustada para explicar o uso dos dados sem apontar para uma política inexistente.

## Instalação no servidor

Extraia o pacote como pasta irmã do projeto atual:

```
/home/imp-tec/htdocs/imp-tec.com/landing-html/
```

O document root público deve ser exatamente:

```
/home/imp-tec/htdocs/imp-tec.com/landing-html/public
```

Não configure o root na pasta `landing-html`: ela contém configuração e dependências privadas.

No CloudPanel, confirme que o site oferece PHP-FPM e selecione PHP 8.2 ou superior. O site atual foi configurado originalmente como PHP; se não houver uma porta PHP-FPM disponível, configure o suporte PHP antes de usar o VHOST fornecido. Os placeholders são processados pelo CloudPanel, não diretamente pelo Nginx.

Com Composer disponível, execute como usuário do site, na pasta `landing-html`:

```bash
composer install --no-dev --prefer-dist --optimize-autoloader
cp config/email.example.php config/email.php
chmod 600 config/email.php
nano config/email.php
```

O arquivo `config/email.php` deve pertencer ao usuário que executa o PHP-FPM. Se o extrair como root, ajuste o proprietário dos arquivos ao usuário do site, por exemplo:

```bash
chown -R imp-tec:imp-tec /home/imp-tec/htdocs/imp-tec.com/landing-html
```

Use a senha real da caixa `calculos@exatocalculos.com.br`. PHPMailer aceita:

- `port => 587`, `encryption => 'tls'`: STARTTLS.
- `port => 465`, `encryption => 'ssl'`: TLS implícito.

As credenciais nunca são inseridas no HTML nem no JavaScript. A senha não está incluída neste pacote. O arquivo `.env` do container anterior não é lido por esta versão PHP.

## Teste antes da troca

Na pasta `landing-html`, se desejar testar via túnel SSH:

```bash
php -S 127.0.0.1:8090 -t public
```

No seu computador:

```bash
ssh -L 8090:127.0.0.1:8090 root@IP_DO_SERVIDOR
```

Abra http://localhost:8090 para verificar layout/menu. O endpoint restringe o Origin aos domínios de produção; para testar o envio no túnel, adicione temporariamente `http://localhost:8090` à lista de origens em `contato.php`, removendo após o teste. Não desligue a verificação TLS SMTP.

## Troca no CloudPanel

1. Guarde a configuração atual do VHOST e o caminho do root para rollback.
2. Ajuste Root Directory para `landing-html/public` (caminho completo acima).
3. Substitua somente o VHOST de `imp-tec.com` pelo conteúdo de `deploy/cloudpanel-vhost.conf`.
4. Confirme certificado para `imp-tec.com` e `www.imp-tec.com`.
5. Valide `nginx -t`. Se passar, recarregue com `systemctl reload nginx` se o painel ainda não tiver aplicado.
6. Confira página, logo, menu móvel e formulário, inclusive a chegada da notificação e confirmação por e-mail.

Não altere o VHOST de `api-dev.imp-tec.com` nem o container `calculai-api` (porta 8085).

Após validar a nova versão, pare apenas o serviço do antigo site, na pasta do projeto Blazor:

```bash
cd /home/imp-tec/htdocs/imp-tec.com/ImptecLadingPage
docker compose stop website
```

Não execute comandos de parada global do Docker. Para rollback, inicie novamente `docker compose up -d website` na pasta antiga e restaure root/VHOST com proxy para 8086.

## Manutenção e SEO

Edite HTML, CSS, JS ou PHP diretamente; sem recompilar. Para atualização de CSS/JS, altere a URL para incluir versão (`landing.js?v=2`) ou atualize o navegador sem cache. Dependências Composer só precisam de atualização quando você decidir atualizar a biblioteca.

Incluídos: title, description, canonical, Open Graph, robots.txt e sitemap.xml. Todo o conteúdo principal já está no HTML. Isso facilita rastreamento, mas não garante indexação nem posição no Google. O Blazor original tinha pré-renderização e não era, por si só, incompatível com SEO. Cadastre `https://imp-tec.com/sitemap.xml` no Search Console após publicar.

A API envia a notificação ao destinatário fixo e tenta enviar uma confirmação ao visitante. Se apenas a confirmação falhar, registra o erro e mantém sucesso para não duplicar o lead. Há validação, honeypot e limite de 5 tentativas por IP em 10 minutos. Não é uma solução completa contra spam; se houver abuso, adicione CAPTCHA. Não há persistência de leads em banco; o SMTP deve estar funcionando para receber contatos.

## Validação e limites

Validação realizada: estrutura das 7 seções, 5 serviços, 4 etapas, 6 depoimentos; sintaxe JavaScript; análise de sintaxe PHP 8.2; testes em DOM do menu móvel, carrossel e estados de sucesso/erro do formulário com SMTP simulado. Não foi possível executar PHP nem renderizar em navegador neste ambiente; valide o layout e o envio SMTP no servidor antes da troca. Credenciais reais e envio SMTP em produção devem ser testados no seu servidor; nenhum e-mail real foi enviado durante a conversão.
