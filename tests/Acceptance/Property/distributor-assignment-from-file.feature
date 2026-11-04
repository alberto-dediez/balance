Feature:
    I want to assign a distributor to the properties listed in a CSV file from the console

    Background:
        Given account "Greenhost" with id "555" exists
        Given account "RH Hoteles" with id "1111" exists
        Given account "Hotel Gaudea" with id "2222" exists
        Given property "HOTEL RH PRINCESA" with id "11" of account "1111" and bioscore id "44397" exists
        Given property "Estudios RH Vinaros Playa" with id "12" of account "1111" exists
        Given property "HOTEL RH SOL" with id "13" of account "1111" and bioscore id "44454" exists
        Given property "HOTEL RH IFACH" with id "14" of account "1111" and bioscore id "42885" exists
        Given property "Hotel Gaudea" with id "21" of account "2222" exists

    Scenario: The properties of the file are assigned to the distributor
        Given account "555" is a distributor
        Given distributor "555" is assigned to property "14"
        When command "property:distributor:assign" is executed with parameters:
            | parameter        | value                                                  |
            | file             | tests/Acceptance/Property/fixtures/distributor-properties.csv |
            | --distributor    | 555                                                    |
            | --name-column    | display_name                                           |
            | --account-column | group                                                  |
        Then Is Executed Successfully
        Then I want to see "cuenta y nombre" in command output
        Then I want to see "repetida (línea 2)" in command output
        Then I want to see "no encontrada" in command output
        Then I want to see "ya asignada" in command output
        Then I want to see "Propiedades adheridas: 3. Errores: 0." in command output
        Then distributor "555" should be assigned to property "11"
        Then distributor "555" should be assigned to property "12"
        Then distributor "555" should be assigned to property "21"
        Then distributor "555" should not be assigned to property "13"

    Scenario: A dry run does not assign any property
        Given account "555" is a distributor
        When command "property:distributor:assign" is executed with parameters:
            | parameter        | value                                                  |
            | file             | tests/Acceptance/Property/fixtures/distributor-properties.csv |
            | --distributor    | 555                                                    |
            | --name-column    | display_name                                           |
            | --account-column | group                                                  |
            | --dry-run        |                                                        |
        Then Is Executed Successfully
        Then I want to see "Se adherirían 4 propiedades." in command output
        Then distributor "555" should not be assigned to property "11"

    Scenario: The account must be a distributor
        When command "property:distributor:assign" is executed with parameters:
            | parameter     | value                                                  |
            | file          | tests/Acceptance/Property/fixtures/distributor-properties.csv |
            | --distributor | 555                                                    |
        Then I expect status code 1
        Then I want to see "no es una cuenta distribuidora activa" in command output
        Then distributor "555" should not be assigned to property "11"
