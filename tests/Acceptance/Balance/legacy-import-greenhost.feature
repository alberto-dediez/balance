@legacy-import
Feature:
    I want to import a legacy Greenhost platform grouping its hotels into accounts with a consultant

    Background:
        Given mobility "Coche" with id "0193a7e0-0000-7000-8000-000000000004" exists

    Scenario: Imports the grouped hotels with a consultant
        When command "balance:legacy:import" is executed with parameters:
            | parameter           | value                                                         |
            | directory           | tests/Acceptance/Balance/fixtures/legacy-greenhost            |
            | --legacy-url        | https://greenhost.example.test                                |
            | --groups            | tests/Acceptance/Balance/fixtures/legacy-greenhost/groups.csv |
            | --consultant        | asociacion                                                    |
            | --create-properties |                                                               |
            | --mobility-car      | 0193a7e0-0000-7000-8000-000000000004                          |
        Then Is Executed Successfully
        Then I want to see "creada:" in command output
        Then I want to see "misma propiedad que duplicado" in command output
        Then I want to see "Consultora asignada a la cuenta Grupo A" in command output
        Then I want to see "Consultora asignada a la cuenta Duplicado" in command output
        Then I want to see "Consultora asignada a la cuenta Hotel Independiente" in command output
        Then I want to see "Usuario marketing@grupo-a.test creado como ROLE_OWNER" in command output
        Then I want to see "Usuario recepcion@duplicado.test creado como ROLE_OWNER" in command output
        Then I want to see "asociacion: 1 cálculos importados" in command output
        Then I want to see "grupo-a-uno: 1 cálculos importados" in command output
        Then I want to see "grupo-a-dos: 1 cálculos importados" in command output
        Then account "Grupo A" should have consultant "Asociación Test"
        Then account "Duplicado" should have consultant "Asociación Test"
        Then account "Grupo A" should have 2 properties
        Then account "Duplicado" should have 1 properties
        Then account "Asociación Test" should be a consultant
        Then account "Asociación Test" should have 0 properties
        Then account "Hotel Independiente" should have consultant "Asociación Test"
        Then account "Hotel Independiente" should have 1 properties
        Then property with bioscore id "1" should belong to account "Grupo A"
        Then property with bioscore id "3" should belong to account "Duplicado"
        Then file "tests/Acceptance/Balance/fixtures/legacy-greenhost/redirects.txt" contains "/carbon-footprint/trip/"
        Then file "tests/Acceptance/Balance/fixtures/legacy-greenhost/redirects.txt" contains "/carbon-footprint/property-trip/"
        Then file "tests/Acceptance/Balance/fixtures/legacy-greenhost/redirects.txt" does not contain "{{PROPERTY_TRIP_URL:"

    Scenario: Running the grouped import twice does not duplicate anything
        When command "balance:legacy:import" is executed with parameters:
            | parameter           | value                                                         |
            | directory           | tests/Acceptance/Balance/fixtures/legacy-greenhost            |
            | --legacy-url        | https://greenhost.example.test                                |
            | --groups            | tests/Acceptance/Balance/fixtures/legacy-greenhost/groups.csv |
            | --consultant        | asociacion                                                    |
            | --create-properties |                                                               |
            | --mobility-car      | 0193a7e0-0000-7000-8000-000000000004                          |
        Then Is Executed Successfully
        When command "balance:legacy:import" is executed with parameters:
            | parameter           | value                                                         |
            | directory           | tests/Acceptance/Balance/fixtures/legacy-greenhost            |
            | --legacy-url        | https://greenhost.example.test                                |
            | --groups            | tests/Acceptance/Balance/fixtures/legacy-greenhost/groups.csv |
            | --consultant        | asociacion                                                    |
            | --create-properties |                                                               |
            | --mobility-car      | 0193a7e0-0000-7000-8000-000000000004                          |
        Then Is Executed Successfully
        Then I want to see "ya existía" in command output
        Then I want to see "ya asignada" in command output
        Then I don't want to see "creada:" in command output
        Then I don't want to see "cálculos importados" in command output
        Then account "Grupo A" should have 2 properties
        Then account "Duplicado" should have 1 properties

    Scenario: The groups file must reference existing organizations
        When command "balance:legacy:import" is executed with parameters:
            | parameter           | value                                                                 |
            | directory           | tests/Acceptance/Balance/fixtures/legacy-greenhost                    |
            | --legacy-url        | https://greenhost.example.test                                        |
            | --groups            | tests/Acceptance/Balance/fixtures/legacy-greenhost/groups-invalid.csv |
            | --consultant        | asociacion                                                            |
            | --create-properties |                                                                       |
            | --mobility-car      | 0193a7e0-0000-7000-8000-000000000004                                  |
        Then I expect status code 1
        Then I want to see "no existe" in command output
        Then I don't want to see "creada:" in command output

    Scenario: The dry run warns about groups without an existing account
        Given account "Grupo A S.L." with id "0193a7e0-0000-7000-8000-000000000010" exists
        When command "balance:legacy:import" is executed with parameters:
            | parameter           | value                                                         |
            | directory           | tests/Acceptance/Balance/fixtures/legacy-greenhost            |
            | --legacy-url        | https://greenhost.example.test                                |
            | --groups            | tests/Acceptance/Balance/fixtures/legacy-greenhost/groups.csv |
            | --consultant        | asociacion                                                    |
            | --create-properties |                                                               |
            | --mobility-car      | 0193a7e0-0000-7000-8000-000000000004                          |
            | --dry-run           |                                                               |
        Then Is Executed Successfully
        Then I want to see "Grupos sin cuenta en Balance" in command output
        Then I want to see "Grupo A S.L. (0193a7e0-0000-7000-8000-000000000010)" in command output
        Then I want to see "2 grupos se crearán como cuentas nuevas" in command output
        Then I want to see "se crearía" in command output
        Then I don't want to see "creada:" in command output

    Scenario: A group whose name matches an account is not warned
        Given account "Grupo A" with id "0193a7e0-0000-7000-8000-000000000011" exists
        When command "balance:legacy:import" is executed with parameters:
            | parameter           | value                                                         |
            | directory           | tests/Acceptance/Balance/fixtures/legacy-greenhost            |
            | --legacy-url        | https://greenhost.example.test                                |
            | --groups            | tests/Acceptance/Balance/fixtures/legacy-greenhost/groups.csv |
            | --consultant        | asociacion                                                    |
            | --create-properties |                                                               |
            | --mobility-car      | 0193a7e0-0000-7000-8000-000000000004                          |
            | --dry-run           |                                                               |
        Then Is Executed Successfully
        Then I want to see "1 grupos se crearán como cuentas nuevas" in command output
        Then I don't want to see "2 grupos se crearán" in command output

    Scenario: Marks the existing consultant account as a consultant
        Given account "Asociación Test" with id "0193a7e0-0000-7000-8000-000000000020" exists
        When command "balance:legacy:import" is executed with parameters:
            | parameter           | value                                                         |
            | directory           | tests/Acceptance/Balance/fixtures/legacy-greenhost            |
            | --legacy-url        | https://greenhost.example.test                                |
            | --groups            | tests/Acceptance/Balance/fixtures/legacy-greenhost/groups.csv |
            | --consultant        | asociacion                                                    |
            | --create-properties |                                                               |
            | --mobility-car      | 0193a7e0-0000-7000-8000-000000000004                          |
        Then Is Executed Successfully
        Then I want to see "marcada como consultora" in command output
        Then account "Asociación Test" should be a consultant
        Then account "Grupo A" should have consultant "Asociación Test"

    Scenario: A reused account with another consultant keeps it
        Given account "Otra Consultora" with id "0193a7e0-0000-7000-8000-000000000021" is a consultant
        Given account "Grupo A" with id "0193a7e0-0000-7000-8000-000000000022" exists
        Given account "0193a7e0-0000-7000-8000-000000000022" has consultant "0193a7e0-0000-7000-8000-000000000021"
        When command "balance:legacy:import" is executed with parameters:
            | parameter           | value                                                         |
            | directory           | tests/Acceptance/Balance/fixtures/legacy-greenhost            |
            | --legacy-url        | https://greenhost.example.test                                |
            | --groups            | tests/Acceptance/Balance/fixtures/legacy-greenhost/groups.csv |
            | --consultant        | asociacion                                                    |
            | --create-properties |                                                               |
            | --mobility-car      | 0193a7e0-0000-7000-8000-000000000004                          |
        Then Is Executed Successfully
        Then I want to see "tiene otra consultora, se mantiene" in command output
        Then account "Grupo A" should have consultant "Otra Consultora"
        Then account "Duplicado" should have consultant "Asociación Test"
