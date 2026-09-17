Feature:
    Ability to launch the Bioscore catalog synchronization from the screen

    Scenario: As admin I enqueue the synchronization and come back to the same screen
        Given user "admin@dediez.software" with role "ROLE_ADMIN" exists
        When I am logged as "admin@dediez.software"
        When I go to "/es/admin/bioscore/refrigerant-gases"
        When I click on link "Sincronizar ahora"
        Then I am redirected to route "admin_bioscore_refrigerant_gases"
        Then I see a text "Sincronización de maestros de Bioscore encolada"

    Scenario: Without a valid token the synchronization is refused
        Given user "admin@dediez.software" with role "ROLE_ADMIN" exists
        When I am logged as "admin@dediez.software"
        When I go to "/es/admin/bioscore/refrigerant_gas/sync"
        Then the response status should be 403

    Scenario: As owner I can't launch the synchronization
        Given user "owner1@dediez.software" with role "ROLE_OWNER" exists
        When I am logged as "owner1@dediez.software"
        When I go to "/es/admin/bioscore/refrigerant_gas/sync"
        Then the response status should be 403
