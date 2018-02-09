@javascript
Feature: Export product models according to text attribute filter
  In order to export specific product models
  As a product manager
  I need to be able to export the product models according to text attribute values

  Background:
    Given a "catalog_modeling" catalog configuration
    And I am logged in as "Julia"

  Scenario: Export products by text values
    When I am on the "csv_product_model_export" export job edit page
    And I visit the "Content" tab
    # And I add available attributes Comment
    And I add available attributes Model name
    And I switch the locale from "name" filter to "en_US"
    And I filter by "name" with operator "Contains" and value "Heritage jacket navy"
    And I press "Save"
    And I should not see the text "There are unsaved changes"
    When I am on the "csv_product_model_export" export job page
    And I launch the export job
    And I wait for the "csv_product_model_export" job to finish
    Then exported file of "csv_product_model_export" should contain:
    """
    code;family_variant;parent;categories;brand;care_instructions;collection;description-de_DE-ecommerce;description-de_DE-mobile;description-de_DE-print;description-en_US-ecommerce;description-en_US-mobile;description-en_US-print;description-fr_FR-ecommerce;description-fr_FR-mobile;description-fr_FR-print;erp_name-de_DE;erp_name-en_US;erp_name-fr_FR;image;keywords-de_DE;keywords-en_US;keywords-fr_FR;material;meta_description-de_DE;meta_description-en_US;meta_description-fr_FR;meta_title-de_DE;meta_title-en_US;meta_title-fr_FR;name-de_DE;name-en_US;name-fr_FR;notice;price-EUR;price-USD;supplier;wash_temperature;weight;weight-unit
    amor;clothing_colorsize;;master_men_blazers,supplier_zaro;;;summer_2016;;;;"Heritage jacket navy blue tweed suit with single breasted 2 button. 53% wool, 22% polyester, 18% acrylic, 5% nylon, 1% cotton, 1% viscose. Dry Cleaning uniquement.Le mannequin measuring 1m85 and wears UK size 40, size 50 FR";;;;;;;Amor;;;;;;;;;;;;;;"Heritage jacket navy";;;999.00;;zaro;800;;
    hermes;clothing_colorsize;;master_men_blazers,supplier_mongo;;;summer_2016;;;;"Suit Jacket Heritage multicolored checkered Prince of Wales with single breasted 2 button and skinny pants to match.";;;;;;;Hermes;;;;;;;;;;;;;;"Suit Jacket Heritage multicolored";;;799.00;;mongo;800;;
    """
