Feature:
    I want to make sure that the system allows marking an emission factor group as the default one

    Scenario: As an admin, I can mark an emission factor group as default
        Given user "admin@dediez.software" with role "ROLE_ADMIN" exists
        Given emission factor group "Group 1" with id "333" exists
        When I am logged as "admin@dediez.software"
        When I go to "/es/admin/emission-factor-groups/set-default/333"
        When I follow redirect
        Then the response status should be 200
        Then I see a text "Se ha marcado Group 1 como grupo de coeficientes por defecto"

    Scenario: Marking a group as default unmarks the previous default one
        Given user "admin@dediez.software" with role "ROLE_ADMIN" exists
        Given emission factor group "Group 1" with id "333" exists
        Given emission factor group "Group 2" with id "444" exists
        When I am logged as "admin@dediez.software"
        When I go to "/es/admin/emission-factor-groups/set-default/333"
        When I go to "/es/admin/emission-factor-groups/set-default/444"
        When I follow redirect
        Then the response status should be 200
        Then I see a text "Se ha marcado Group 2 como grupo de coeficientes por defecto"

    Scenario: As normal user I can't mark an emission factor group as default
        Given user "admin@dediez.software" with role "ROLE_ADMIN" exists
        Given user "user1@dediez.software" with role "ROLE_USER" exists
        Given emission factor group "Group 1" with id "333" exists
        When I am logged as "user1@dediez.software"
        When I go to "/es/admin/emission-factor-groups/set-default/333"
        Then the response status should be 403
