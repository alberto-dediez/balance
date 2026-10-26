@legacy-import
Feature:
    I want to import step by step the accounts, users, calculations and contributions of a legacy Balance platform

    Background:
        Given account "Hotel Test" with id "0193a7e0-0000-7000-8000-000000000001" exists
        Given property "Hotel Test Playa" with id "0193a7e0-0000-7000-8000-000000000002" of account "0193a7e0-0000-7000-8000-000000000001" exists
        Given mobility "Avión" with id "0193a7e0-0000-7000-8000-000000000003" exists
        Given mobility "Coche" with id "0193a7e0-0000-7000-8000-000000000004" exists
        Given project "Programa CAMINA" with id "0193a7e0-0000-7000-8000-000000000005" and price per tonne "25" exists

    Scenario: The import runs without interaction with the equivalent options
        When command "balance:legacy:import" is executed with parameters:
            | parameter        | value                                                                 |
            | directory        | tests/Acceptance/Balance/fixtures/legacy-balance                      |
            | --legacy-url     | https://balance.example.test                                          |
            | --skip           | test-org                                                              |
            | --staff          | test-org                                                              |
            | --property       | hotel-test=0193a7e0-0000-7000-8000-000000000002                       |
            | --mobility-plane | 0193a7e0-0000-7000-8000-000000000003                                  |
            | --mobility-car   | 0193a7e0-0000-7000-8000-000000000004                                  |
            | --project        | 66e15d7743e6391cc4900001=0193a7e0-0000-7000-8000-000000000005         |
        Then Is Executed Successfully
        Then I want to see "creada:" in command output
        Then I want to see "Usuario owner@example.test creado como ROLE_OWNER" in command output
        Then I want to see "Usuario external@example.test creado como ROLE_USER" in command output
        Then I want to see "personal" in command output
        Then I want to see "viajes-test: 1 cálculos importados" in command output
        Then I want to see "hotel-test: 1 cálculos importados" in command output
        Then I want to see "1 (sin referral)" in command output
        Then I want to see "Aportación c0063a20-fe4e-48c6-b91a-fed92e6e4ebc importada" in command output
        Then I want to see "sin equivalente" in command output
        Then there should be 1 contributions
        Then the only contribution should be "paid"
        Then user "tester@example.test" does not exist

    Scenario: The import runs interactively choosing each answer
        Given the command answers are:
            | answer                                                       |
            | 1                                                            |
            | no                                                           |
            | Hotel Test (0193a7e0-0000-7000-8000-000000000001)            |
            | no                                                           |
            | 0                                                            |
            | yes                                                          |
            | yes                                                          |
            | Hotel Test Playa (0193a7e0-0000-7000-8000-000000000002)      |
            | yes                                                          |
            | Avión (0193a7e0-0000-7000-8000-000000000003)                 |
            | Coche (0193a7e0-0000-7000-8000-000000000004)                 |
            | yes                                                          |
            |                                                              |
            |                                                              |
            | yes                                                          |
            | yes                                                          |
        When command "balance:legacy:import" is executed with parameters:
            | parameter    | value                                            |
            | directory    | tests/Acceptance/Balance/fixtures/legacy-balance |
            | --legacy-url | https://balance.example.test                     |
        Then Is Executed Successfully
        Then I want to see "creada:" in command output
        Then I want to see "Usuario owner@example.test creado como ROLE_OWNER" in command output
        Then I want to see "Usuario external@example.test creado como ROLE_USER" in command output
        Then I want to see "personal" in command output
        Then I want to see "viajes-test: 1 cálculos importados" in command output
        Then I want to see "hotel-test: 1 cálculos importados" in command output
        Then I want to see "1 (sin referral)" in command output
        Then I want to see "Aportación c0063a20-fe4e-48c6-b91a-fed92e6e4ebc importada" in command output
        Then I want to see "sin equivalente" in command output
        Then there should be 1 contributions

    Scenario: Rejecting the users step creates the accounts but no user
        Given the command answers are:
            | answer                                                       |
            | 1                                                            |
            | no                                                           |
            | Hotel Test (0193a7e0-0000-7000-8000-000000000001)            |
            | no                                                           |
            | 0                                                            |
            | yes                                                          |
            | yes                                                          |
            | Hotel Test Playa (0193a7e0-0000-7000-8000-000000000002)      |
            | no                                                           |
            | Avión (0193a7e0-0000-7000-8000-000000000003)                 |
            | Coche (0193a7e0-0000-7000-8000-000000000004)                 |
            | yes                                                          |
            |                                                              |
            |                                                              |
            | yes                                                          |
            | yes                                                          |
        When command "balance:legacy:import" is executed with parameters:
            | parameter    | value                                            |
            | directory    | tests/Acceptance/Balance/fixtures/legacy-balance |
            | --legacy-url | https://balance.example.test                     |
        Then Is Executed Successfully
        Then I want to see "creada:" in command output
        Then I want to see "Paso omitido: no se crea ningún usuario." in command output
        Then user "owner@example.test" does not exist
        Then user "external@example.test" does not exist

    Scenario: Running the import twice does not duplicate anything
        When command "balance:legacy:import" is executed with parameters:
            | parameter        | value                                                         |
            | directory        | tests/Acceptance/Balance/fixtures/legacy-balance              |
            | --legacy-url     | https://balance.example.test                                  |
            | --skip           | test-org                                                      |
            | --staff          | test-org                                                      |
            | --mobility-plane | 0193a7e0-0000-7000-8000-000000000003                          |
            | --mobility-car   | 0193a7e0-0000-7000-8000-000000000004                          |
            | --project        | 66e15d7743e6391cc4900001=0193a7e0-0000-7000-8000-000000000005 |
        Then Is Executed Successfully
        When command "balance:legacy:import" is executed with parameters:
            | parameter        | value                                                         |
            | directory        | tests/Acceptance/Balance/fixtures/legacy-balance              |
            | --legacy-url     | https://balance.example.test                                  |
            | --skip           | test-org                                                      |
            | --staff          | test-org                                                      |
            | --mobility-plane | 0193a7e0-0000-7000-8000-000000000003                          |
            | --mobility-car   | 0193a7e0-0000-7000-8000-000000000004                          |
            | --project        | 66e15d7743e6391cc4900001=0193a7e0-0000-7000-8000-000000000005 |
        Then Is Executed Successfully
        Then I want to see "ya existía" in command output
        Then I don't want to see "creada:" in command output
        Then I don't want to see "cálculos importados" in command output
        Then I don't want to see "importada" in command output
        Then there should be 1 contributions

    Scenario: The import fails without the legacy url
        When command "balance:legacy:import" is executed with parameters:
            | parameter        | value                                            |
            | directory        | tests/Acceptance/Balance/fixtures/legacy-balance |
            | --mobility-plane | 0193a7e0-0000-7000-8000-000000000003             |
            | --mobility-car   | 0193a7e0-0000-7000-8000-000000000004             |
        Then I expect status code 1
        Then I want to see "legacy-url" in command output
        Then I don't want to see "creada:" in command output

    Scenario: The import fails without the mobility of a transport with calculations
        When command "balance:legacy:import" is executed with parameters:
            | parameter      | value                                            |
            | directory      | tests/Acceptance/Balance/fixtures/legacy-balance |
            | --legacy-url   | https://balance.example.test                     |
            | --mobility-car | 0193a7e0-0000-7000-8000-000000000004             |
        Then I expect status code 1
        Then I want to see "--mobility-plane" in command output
        Then I don't want to see "creada:" in command output

    Scenario: The dry run does not write in the database but writes the redirects
        When command "balance:legacy:import" is executed with parameters:
            | parameter        | value                                                         |
            | directory        | tests/Acceptance/Balance/fixtures/legacy-balance              |
            | --legacy-url     | https://balance.example.test                                  |
            | --skip           | test-org                                                      |
            | --mobility-plane | 0193a7e0-0000-7000-8000-000000000003                          |
            | --mobility-car   | 0193a7e0-0000-7000-8000-000000000004                          |
            | --project        | 66e15d7743e6391cc4900001=0193a7e0-0000-7000-8000-000000000005 |
            | --dry-run        |                                                               |
        Then Is Executed Successfully
        Then I want to see "se crearía" in command output
        Then I don't want to see "creada:" in command output
        Then there should be 0 contributions
        Then user "owner@example.test" does not exist
        Then file "tests/Acceptance/Balance/fixtures/legacy-balance/redirects.txt" contains "{{DRY_RUN}}"

    Scenario: The redirects point to the trip calculators of the account and the property
        When command "balance:legacy:import" is executed with parameters:
            | parameter        | value                                            |
            | directory        | tests/Acceptance/Balance/fixtures/legacy-balance |
            | --legacy-url     | https://balance.example.test                     |
            | --skip           | test-org                                         |
            | --mobility-plane | 0193a7e0-0000-7000-8000-000000000003             |
            | --mobility-car   | 0193a7e0-0000-7000-8000-000000000004             |
        Then Is Executed Successfully
        Then file "tests/Acceptance/Balance/fixtures/legacy-balance/redirects.txt" contains "RewriteCond %{HTTP_HOST} ^balance\.example\.test$ [NC]"
        Then file "tests/Acceptance/Balance/fixtures/legacy-balance/redirects.txt" contains "/carbon-footprint/trip/"
        Then file "tests/Acceptance/Balance/fixtures/legacy-balance/redirects.txt" contains "/carbon-footprint/property-trip/"
        Then file "tests/Acceptance/Balance/fixtures/legacy-balance/redirects.txt" contains "# test-org: omitida"
        Then file "tests/Acceptance/Balance/fixtures/legacy-balance/redirects.txt" does not contain "{{PROPERTY_TRIP_URL:"

    Scenario: The redirects leave a marker for an organization without property
        Given account "Otra cuenta" with id "0193a7e0-0000-7000-8000-000000000006" exists
        When command "balance:legacy:import" is executed with parameters:
            | parameter        | value                                                       |
            | directory        | tests/Acceptance/Balance/fixtures/legacy-balance            |
            | --legacy-url     | https://balance.example.test                                |
            | --skip           | test-org                                                    |
            | --account        | hotel-test=0193a7e0-0000-7000-8000-000000000006             |
            | --mobility-plane | 0193a7e0-0000-7000-8000-000000000003                        |
        Then Is Executed Successfully
        Then file "tests/Acceptance/Balance/fixtures/legacy-balance/redirects.txt" contains "{{PROPERTY_TRIP_URL:hotel-test}}"
