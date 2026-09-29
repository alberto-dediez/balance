Feature:
    Ability to list the categories of mitigation measures

    Scenario: As admin I can access the list of categories
        Given user "admin@dediez.software" with role "ROLE_ADMIN" exists
        Given mitigation measure category "Energía" with id "1111" exists
        Given mitigation measure category "Movilidad" with id "2222" exists
        When I am logged as "admin@dediez.software"
        When I go to "/es/admin/mitigation-measure-categories"
        Then the response status should be 200
        Then I see a text "Energía"
        Then I see a text "Movilidad"

    Scenario: As owner I cannot access the list of categories
        Given account "MyCompany" with id "666" exists
        Given user "owner@dediez.software" with role "ROLE_OWNER" of account "666" exists
        When I am logged as "owner@dediez.software"
        When I go to "/es/admin/mitigation-measure-categories"
        Then the response status should be 403
