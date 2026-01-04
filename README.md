<p align="center">
  <img src="icon.png" width="128" alt="SmartRollback Icon">
</p>

# 🛡️ SmartRollback

**SmartRollback** é uma solução utilitária de alta performance para PocketMine-MP (API 5.x) desenvolvida para servidores Survival/SMP. Ele permite que administradores monitorem ações e revertam danos (griefing ou roubos) de forma precisa e assíncrona, garantindo a estabilidade do TPS.

---

## 🔍 Visão Geral

No cenário de um servidor Survival/SMP, o "griefing" é inevitável. O SmartRollback resolve este problema permitindo que administradores:
- Identifiquem quem quebrou ou colocou qualquer bloco.
- Revertam roubos de itens em baús sem resetar o inventário do jogador (logando interações).
- Desfaçam alterações em áreas específicas sem afetar o resto do mapa.

Toda a lógica de banco de dados e leitura de arquivos é feita fora da thread principal, eliminando os famosos "engasgos" (*lag spikes*) comuns em plugins de log antigos.

---

## ✨ Recursos Principais

* **⚡ Async Database Engine:** Gravação e leitura de logs via SQLite3 utilizando `AsyncTask` e `WAL Mode`.
* **⏳ Controle Temporal:** Reversão baseada em strings de tempo intuitivas (ex: `15m`, `3h`, `2d`).
* **🏗️ Rollback Inteligente:** Restaura estados complexos de blocos (NBT básico e metadados da API 5.x).
* **🧹 Auto-Cleanup:** Sistema de TTL (Time To Live) que deleta registros antigos automaticamente para manter o arquivo de banco de dados leve.
* **📉 Baixo Consumo de I/O:** Agrupamento de logs em memória antes da escrita física no disco.

---

## 🚀 Diferenciais Técnicos

Diferente de outros plugins de rollback, o SmartRollback foca em:
* **Zero Main-Thread Lag:** Gravação e leitura de dados via SQLite são feitas em threads separadas (`AsyncTask`).
* **Batch Logging:** Agrupa eventos de blocos em memória e os escreve em lotes, reduzindo o I/O do disco.
* **Incremental Restoration:** O rollback não congela o servidor; ele coloca blocos progressivamente a cada tick.
* **SQLite WAL Mode:** Ativa o modo *Write-Ahead Logging* para permitir leituras e escritas simultâneas ultra-rápidas.

---

## 🛠️ Detalhes da Implementação (API 5.x)

Para garantir a máxima compatibilidade e performance na API 5.x, o SmartRollback utiliza:
* **GlobalBlockStateHandlers:** Para a serialização e desserialização precisa de estados de blocos (incluindo propriedades como direção de escadas, cores de lã, etc).
* **LIFO (Last-In-First-Out):** As operações são recuperadas do banco de dados em ordem cronológica inversa. Isso garante que, se um bloco foi alterado múltiplas vezes, a reversão restaurará cada estado corretamente até o ponto desejado.
* **Non-Blocking Queries:** As consultas ao SQLite são processadas em segundo plano. O administrador pode continuar executando outros comandos enquanto o plugin busca os registros.

---

## ⌨️ Comandos e Permissões

| Comando | Descrição | Permissão |
| :--- | :--- | :--- |
| `/rb help` | Exibe os comandos disponíveis. | `smartrollback.admin` |
| `/rb player <nome> <tempo>` | Reverte ações de um jogador específico. | `smartrollback.admin` |
| `/rb area <raio> <tempo>` | Reverte blocos modificados em um raio. | `smartrollback.admin` |
| `/rb undo` | Desfaz a última operação de rollback. | `smartrollback.admin` |

**Formatos de Tempo suportados:**
- `s` (segundos), `m` (minutos), `h` (horas), `d` (dias).
- Exemplo: `/rb player Steve 30m` (Reverte as ações do Steve na última meia hora).

---

## 📦 Como Instalar

1. **Baixe o Plugin:** Obtenha o arquivo `SmartRollback.phar` através do link oficial abaixo:
   > 🔗 **Download:** [poggit.pmmp.io/p/SmartRollback](https://poggit.pmmp.io/p/SmartRollback)

2. **Instalação no Servidor:**
   * Mova o arquivo `.phar` para a pasta `/plugins/` do seu servidor PocketMine-MP.
   * Reinicie o servidor para carregar o plugin e gerar a pasta de dados inicial.

3. **Configuração Final:**
   * Acesse `plugin_data/SmartRollback/config.yml` e ajuste os valores conforme a capacidade do seu hardware.
   * Certifique-se de que os administradores possuem a permissão `smartrollback.admin`.

---

### 🛡️ Nota para Revisores (Poggit)
Este plugin foi desenvolvido seguindo rigorosamente as **diretrizes de submissão** da plataforma:
* **Sem conexões externas:** Não realiza chamadas para APIs externas ou sistemas de licenciamento remoto.
* **Non-Blocking I/O:** Todas as operações de leitura e escrita em banco de dados SQLite são executadas via `AsyncTask`, garantindo que a **Main Thread** permaneça livre para o processamento do jogo.
* **Gerenciamento de Memória:** Utiliza buffers controlados para evitar vazamentos de memória (memory leaks) durante grandes operações de rollback.


## ⚙️ Configuração (`config.yml`)

```yaml
# Configuração do Banco de Dados
database:
  filename: "smart_rollback.sqlite"
  # Tempo de vida dos registros (em dias)
  ttl-days: 7

# Performance e Otimização
performance:
  # Quantidade de eventos para acumular antes de salvar no disco
  write-batch-size: 50
  # Intervalo de flush forçado (em ticks)
  flush-interval: 120
  # Quantidade de blocos restaurados por tick durante o rollback
  rollback-speed: 100

# Limites de Segurança
limits:
  max-radius: 50
