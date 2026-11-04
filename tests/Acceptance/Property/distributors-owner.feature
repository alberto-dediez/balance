Feature:
    As the owner of an adhered account I want to see and remove the distributors of my account and properties

    Background:
        Given account "Greenhost" with id "555" exists
        Given account "555" is a distributor
        Given user "owner1@greenhost.test" with role "ROLE_OWNER" of account "555" exists
        Given user "owner2@greenhost.test" with role "ROLE_OWNER" of account "555" exists
        Given account "Otra Red" with id "666" exists
        Given account "666" is a distributor
        Given account "Hotel A" with id "1111" exists
        Given user "owner@hotela.test" with role "ROLE_OWNER" of account "1111" exists
        Given account "Hotel B" with id "2222" exists
        Given user "owner@hotelb.test" with role "ROLE_OWNER" of account "2222" exists
        Given property "Hotel Playa" with id "11" of account "1111" exists
        Given property "Hotel Rio" with id "21" of account "2222" exists
        Given distributor "555" is assigned to account "1111"
        Given distributor "666" is assigned to property "11"
        Given distributor "555" is assigned to property "21"

    Scenario: As an owner, I see the distributors of my account and of my properties
        When I am logged as "owner@hotela.test"
        When I go to "/es/distributors"
        Then the response status should be 200
        Then I see a table with 3 rows
        Then I see a text "Greenhost"
        Then I see a text "Otra Red"
        Then I see a text "Toda la cuenta"
        Then I see a text "Hotel Playa"
        Then I do not see a text "Hotel Rio"

    Scenario: As an owner without distributors, I see the empty state
        Given account "Hotel C" with id "3333" exists
        Given user "owner@hotelc.test" with role "ROLE_OWNER" of account "3333" exists
        When I am logged as "owner@hotelc.test"
        When I go to "/es/distributors"
        Then the response status should be 200
        Then I see a text "Ningún distribuidor tiene acceso a tu cuenta ni a tus propiedades."

    Scenario: As an owner, I remove a distributor and its owners are notified
        When I am logged as "owner@hotelb.test"
        When I go to "/es/distributors"
        When I click on the action "Eliminar"
        When I follow redirect
        Then the response status should be 200
        Then I see a text "Se ha retirado el distribuidor Greenhost"
        Then I see a text "Ningún distribuidor tiene acceso a tu cuenta ni a tus propiedades."
        Then user "owner1@greenhost.test" has 1 notifications with subject "Se ha retirado tu acceso como distribuidor"
        Then user "owner2@greenhost.test" has 1 notifications with subject "Se ha retirado tu acceso como distribuidor"

    Scenario: As a foreign owner, I can not remove the distributor of another account
        When I am logged as "owner@hotelb.test"
        When I go to "/es/distributors"
        Then I do not see a text "Hotel Playa"
        When I am logged as "owner@hotela.test"
        When I go to "/es/distributors"
        Then I see a text "Greenhost"

    Scenario: Removing a property removes its distributor assignments
        When I am logged as "owner@hotelb.test"
        When I go to "/es/properties/remove/21" with csrf token "remove"
        When I follow redirect
        Then I see a text "Se ha eliminado la propiedad"
        Then user "owner1@greenhost.test" has 0 notifications with subject "Se ha retirado tu acceso como distribuidor"
        Given user "admin@dediez.software" with role "ROLE_ADMIN" exists
        When I am logged as "admin@dediez.software"
        When I go to "/es/admin/distributor-assignments"
        Then I see a table with 4 rows
