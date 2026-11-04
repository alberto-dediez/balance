Feature:
    I want to assign a distributor to several accounts at once from the accounts list

    Scenario: As an admin, I can assign a distributor to the selected accounts
        Given user "admin@dediez.software" with role "ROLE_ADMIN" exists
        Given account "Hotel A" with id "1111" exists
        Given account "Hotel B" with id "2222" exists
        Given account "Greenhost" with id "555" exists
        Given account "555" is a distributor
        When I am logged as "admin@dediez.software"
        When I go to "/es/admin/accounts"
        When I submit a form with "elements:[1111,2222]|action:assign-distributor|distributorAccountId:555" and button "Realizar"
        When I follow redirect
        Then the response status should be 200
        Then I see a text "Se ha asignado el distribuidor seleccionado a la cuenta"
        When I go to "/es/admin/distributor-assignments"
        Then I see a text "Hotel A"
        Then I see a text "Hotel B"
        Then I see a text "Toda la cuenta"

    Scenario: As an admin, assigning the same distributor twice does not duplicate the assignment
        Given user "admin@dediez.software" with role "ROLE_ADMIN" exists
        Given account "Hotel A" with id "1111" exists
        Given user "owner@hotela.test" with role "ROLE_OWNER" of account "1111" exists
        Given account "Greenhost" with id "555" exists
        Given account "555" is a distributor
        Given distributor "555" is assigned to account "1111"
        When I am logged as "admin@dediez.software"
        When I go to "/es/admin/accounts"
        When I submit a form with "elements:[1111]|action:assign-distributor|distributorAccountId:555" and button "Realizar"
        When I follow redirect
        Then I see a text "Se ha asignado el distribuidor seleccionado a la cuenta"
        When I am logged as "owner@hotela.test"
        When I go to "/es/distributors"
        Then I see a table with 2 rows

    Scenario: As an admin, I cannot assign a distributor to the distributor itself
        Given user "admin@dediez.software" with role "ROLE_ADMIN" exists
        Given account "Greenhost" with id "555" exists
        Given account "555" is a distributor
        When I am logged as "admin@dediez.software"
        When I go to "/es/admin/accounts"
        When I submit a form with "elements:[555]|action:assign-distributor|distributorAccountId:555" and button "Realizar"
        When I follow redirect
        Then the response status should be 200
        Then I see a text "No se ha podido asignar el distribuidor seleccionado a la cuenta"

    Scenario: As an admin, unmarking a distributor removes its assignments and hides it from the selector
        Given user "admin@dediez.software" with role "ROLE_ADMIN" exists
        Given account "Hotel B" with id "2222" exists
        Given account "Greenhost" with id "555" exists
        Given account "555" is a distributor
        Given distributor "555" is assigned to account "2222"
        When I am logged as "admin@dediez.software"
        When I go to "/es/admin/accounts/edit/555"
        When I submit a form with "name:Greenhost|active:1|distributor:0" and button "Guardar"
        When I go to "/es/admin/distributor-assignments"
        Then I do not see a text "Toda la cuenta"
        When I go to "/es/admin/accounts"
        Then I do not see an option "555" in form field "[distributorAccountId]"
