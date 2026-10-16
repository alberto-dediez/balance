Feature:
    Ability to synchronize the Bioscore catalogs from the console

    Scenario: A new item with a single matching master is mapped automatically
        Given I am the system
        Given user "admin@dediez.software" with role "ROLE_ADMIN" exists
        Given combustible "Gas natural" with id "aaaaaaaa-aaaa-aaaa-aaaa-aaaaaaaaaaaa" exists
        Given Bioscore catalog "facility_fuel" contains item "7" named "Gas Natural" with unit ""
        When command "bioscore:catalogs:sync" is executed with argument "username:admin@dediez.software"
        Then I expect status code "0"
        Then Bioscore catalog item "7" of catalog "facility_fuel" should exist
        Then I want to see "Auto-mapped" in command output

    Scenario: A new item with two matching masters stays pending
        Given I am the system
        Given user "admin@dediez.software" with role "ROLE_ADMIN" exists
        Given transport "Gasoil" with id "bbbbbbbb-bbbb-bbbb-bbbb-bbbbbbbbbbbb" exists
        Given transport "Gasoil" with id "cccccccc-cccc-cccc-cccc-cccccccccccc" exists
        Given Bioscore catalog "transport_fuel" contains item "3" named "Gasoil" with unit ""
        When command "bioscore:catalogs:sync" is executed with argument "username:admin@dediez.software"
        Then I expect status code "0"
        Then I want to see "New unmapped items" in command output
        Then I want to see "Gasoil" in command output

    Scenario: An unavailable catalog is reported and the command still succeeds
        Given I am the system
        Given user "admin@dediez.software" with role "ROLE_ADMIN" exists
        Given Bioscore catalog "refrigerant_gas" is unavailable
        Given Bioscore catalog "facility_fuel" contains item "7" named "Gas Natural" with unit ""
        When command "bioscore:catalogs:sync" is executed with argument "username:admin@dediez.software"
        Then I expect status code "0"
        Then I want to see "refrigerant_gas" in command output

    Scenario: An item that is no longer offered is withdrawn
        Given I am the system
        Given user "admin@dediez.software" with role "ROLE_ADMIN" exists
        Given Bioscore catalog item "1" of catalog "facility_fuel" named "Gas natural" with id "11111111-1111-1111-1111-111111111111" exists
        When command "bioscore:catalogs:sync" is executed with argument "username:admin@dediez.software"
        Then I expect status code "0"
        Then Bioscore catalog item "11111111-1111-1111-1111-111111111111" should be withdrawn

    Scenario: The administrators are notified about the new unmapped items
        Given I am the system
        Given user "admin@dediez.software" with role "ROLE_ADMIN" exists
        Given Bioscore catalog "facility_fuel" contains item "7" named "Gas Natural" with unit ""
        When I am logged as "admin@dediez.software"
        When I go to "/es/admin/bioscore/facility-fuels"
        When command "bioscore:catalogs:sync" is executed with argument "username:admin@dediez.software"
        Then I expect status code "0"
        When I go to "/es/notifications"
        Then I see a text "Maestros de Bioscore pendientes de mapear"
