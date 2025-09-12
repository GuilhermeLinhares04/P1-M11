# Cluster Kubernetes com Escalabilidade e HPA

## Objetivo
O objetivo deste projeto é desenvolver um cluster Kubernetes com escalabilidade, utilizando o Horizontal Pod Autoscaler (HPA) para ajustar automaticamente o número de réplicas de uma aplicação PHP/Apache com base no uso de CPU. A solução foi desenvolvida e testada utilizando o Minikube como provedor de cluster local.

## Arquivos do Projeto
Aqui está uma explicação de cada arquivo contido neste repositório:

- ``app/index.php``: Esta é a aplicação PHP. Ela foi projetada para ser computacionalmente intensiva, executando um loop com cálculos para simular o uso de CPU e, assim, acionar o HPA durante os testes de carga.

- ``Dockerfile``: Define a imagem Docker da aplicação. Ele usa uma imagem base oficial do php:apache, copia o arquivo index.php para o diretório do servidor web e expõe a porta 80.

- ``deployment.yaml``: Manifesto do Kubernetes que descreve o Deployment da nossa aplicação. Ele especifica:

    - A imagem a ser utilizada (php-hpa-app).

    - O número inicial de réplicas (1).

    - A solicitação de recursos (requests.cpu: "200m"), que é fundamental para o HPA saber qual é a base de 100% de uso de CPU para um pod.

- ``service.yaml``: Manifesto que cria um Service do tipo NodePort. Ele expõe o Deployment para que possamos acessá-lo de fora do cluster, direcionando o tráfego da porta do nó para a porta 80 dos pods.

- ``hpa.yaml``: Manifesto que configura o Horizontal Pod Autoscaler. Ele define:

    - O alvo do escalonamento (o Deployment php-apache).

    - O número mínimo (1) e máximo (10) de réplicas.

    - A métrica para o auto-scaling: o HPA irá criar novas réplicas sempre que o uso médio de CPU dos pods ultrapassar 50% do valor solicitado no deployment.yaml.

## Pré-requisitos
Antes de começar, garanta que você tenha as seguintes ferramentas instaladas e configuradas:

- **Docker Desktop**

- **kubectl** (Interface de linha de comando do Kubernetes)

- **Minikube** (Para criação do cluster local)

- **PowerShell** (Recomendado para usuários Windows)

## Tutorial de Execução
Siga os passos abaixo para configurar e testar o ambiente.

1. Construa a Imagem Docker
Primeiro, precisamos construir a imagem Docker da nossa aplicação e carregá-la no ambiente do Minikube para que o cluster possa encontrá-la.

    ```
    # Construa a imagem a partir do Dockerfile
    docker build -t php-hpa-app .

    # Carregue a imagem para dentro do Minikube
    minikube image load php-hpa-app
    ```

2. Inicie o Cluster e o Metrics Server
Com a imagem pronta, inicie o cluster Kubernetes com o Minikube e habilite o Metrics Server, que é essencial para o HPA coletar métricas de uso de recursos.

    ```
    # Inicie o cluster
    minikube start

    # Habilite o addon Metrics Server
    minikube addons enable metrics-server
    ```

3. Aplique os Manifestos do Kubernetes
Agora, aplique todos os arquivos de configuração .yaml para criar o Deployment, o Service e o HPA no cluster.

    ```
    # Aplique todos os arquivos de uma vez
    kubectl apply -f deployment.yaml, service.yaml, hpa.yaml
    ```

4. Verifique a Implantação
Confirme se o pod está em execução e o HPA está monitorando. Pode levar um ou dois minutos para o HPA começar a exibir o uso de CPU.

    ```
    # Verifique se o pod está com o status "Running"
    kubectl get pods

    # Verifique o status inicial do HPA
    kubectl get hpa
    # A saída inicial em TARGETS pode ser <unknown>/50%
    ```

5. Execute o Teste de Carga
Para testar o auto-scaling, vamos gerar uma carga intensa de requisições para a nossa aplicação.

    ```
    # Primeiro, obtenha a URL de acesso ao serviço
    minikube service php-apache-service --url

    # Copie a URL retornada e use-a no comando abaixo em um NOVO terminal.
    # Este loop fará requisições contínuas, aumentando o uso de CPU.
    while ($true) { curl -s http://URL-DO-SEU-SERVICO | Out-Null }
    ```

    _Substitua ```http://URL-DO-SEU-SERVICO``` pela URL que você obteve no passo anterior._

6. Monitore o Auto-Scaling em Tempo Real
Em outro terminal, observe o HPA em ação. Você verá o número de réplicas aumentar à medida que o uso de CPU sobe.

### Use este comando para monitorar o HPA a cada 2 segundos
```
while ($true) { kubectl get hpa; Start-Sleep -Seconds 2; Clear-Host }
```

7. Analise os Resultados
    Comportamento Observado:

    Ao iniciar o teste de carga, o uso de CPU (coluna TARGETS) rapidamente ultrapassou a meta de 50%. Em resposta, o HPA começou a provisionar novos pods, e o número de REPLICAS aumentou de 1 para 2, 3, e assim por diante, até que o uso médio de CPU se estabilizasse abaixo da meta ou atingisse o máximo de 10 réplicas.

    Exemplo de saída durante o pico do teste:

    ```
    NAME             REFERENCE               TARGETS   MINPODS   MAXPODS   REPLICAS   AGE
    php-apache-hpa   Deployment/php-apache   150%/50%  1         10        3          10m
    ```

    Para encerrar o teste, pressione Ctrl + C no terminal onde o loop de curl está rodando. Após alguns minutos, você observará o processo inverso: o uso de CPU cairá e o HPA irá reduzir o número de réplicas de volta para 1 (scale down).

8. Limpeza do Ambiente
    Após concluir os testes, você pode remover todos os recursos criados e parar o cluster para liberar recursos da sua máquina.

### Delete os recursos criados pelos arquivos .yaml
    ```
    kubectl delete -f deployment.yaml, service.yaml, hpa.yaml
    ```

### Pare o cluster Minikube
    ```
    minikube stop
    ```

### (Opcional) Delete o cluster completamente
    ```
    minikube delete
    ```