Feature:
    I want to assign a consultant to several accounts at once from the accounts list

    Scenario: As an admin, I can assign a consultant to the selected accounts
        Given user "admin@dediez.software" with role "ROLE_ADMIN" exists
        Given account "Minotour" with id "1111" exists
        Given account "Bioscore" with id "2222" exists
        Given account "TheConsultant" with id "444" is a consultant
        When I am logged as "admin@dediez.software"
        When I go to "/es/admin/accounts"
        When I submit a form with "elements:[1111,2222]|action:assign-consultant|consultantAccountId:444" and button "Realizar"
        When I follow redirect
        Then the response status should be 200
        Then I see a text "Se ha asignado la consultora seleccionada a la cuenta"

    Scenario: As an admin, I cannot assign a consultant to another consultant
        Given user "admin@dediez.software" with role "ROLE_ADMIN" exists
        Given account "Minotour" with id "1111" is a consultant
        Given account "TheConsultant" with id "444" is a consultant
        When I am logged as "admin@dediez.software"
        When I go to "/es/admin/accounts"
        When I submit a form with "elements:[1111]|action:assign-consultant|consultantAccountId:444" and button "Realizar"
        When I follow redirect
        Then the response status should be 200
        Then I see a text "No se ha podido asignar la consultora seleccionada a la cuenta"
