Feature:
    I want to make sure that the system is allow to remove a use profile

    Scenario: As an admin, I can access to remove use profiles
        Given user "admin@dediez.software" with role "ROLE_ADMIN" exists
        Given use profile "Item 1" with id "333" exists
        When I am logged as "admin@dediez.software"
        When I go to "/es/admin/use-profiles/remove/333" with csrf token "remove"
        When I follow redirect
        Then the response status should be 200
        Then I see a text "Se ha eliminado el perfil de uso Item 1"

    Scenario: As normal user I can't remove use profiles
        Given user "admin@dediez.software" with role "ROLE_ADMIN" exists
        Given user "user1@dediez.software" with role "ROLE_USER" exists
        Given use profile "Item 1" with id "333" exists
        When I am logged as "user1@dediez.software"
        When I go to "/es/admin/use-profiles/remove/333" with csrf token "remove"
        Then the response status should be 403
